<?php
/**
 * SiteLock Logger - structured JSONL logger for plugin
 *
 * Place in: includes/logging/class-sitelock-logger.php
 */

defined('ABSPATH') || exit;

class SiteLock_Logger
{
    /** @var SiteLock_Logger|null */
    private static $instance = null;

    /** @var string absolute path to log directory */
    private $log_dir;

    /** @var string absolute path to log file */
    private $log_file;

    /** @var int max file size in bytes before rotating (default 5 MB) */
    private $max_size = 5242880;

    /** @var string log filename */
    private $file_name = 'sitelock-error.log';
    /** @var WP_Filesystem_Base|null Reusable WP Filesystem instance */
    private $fs = null;

    private function __construct()
    {
        $this->ensure_fs();
        $upload_dir     = wp_upload_dir(); // Get the uploads directory
        $this->log_dir  = $upload_dir['basedir'] . '/sitelock-logs';
        $this->log_file = $this->log_dir . '/' . $this->file_name;

        $this->ensure_log_dir();
        $this->protect_log_dir();
    }

    /**
     * Initialize and cache WP_Filesystem in $this->fs.
     *
     * Call $this->ensure_fs() inside any method needing filesystem access.
     *
     * @return bool True if filesystem available, false otherwise.
     */
    private function ensure_fs()
    {
        if ($this->fs instanceof WP_Filesystem_Base) {
            return true;
        }

        if (! function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;
        if (! $wp_filesystem) {
            WP_Filesystem();
        }
        if (! $wp_filesystem) {
            return false;
        }

        $this->fs = $wp_filesystem;

        return true;
    }

    /**
     * Get singleton instance.
     *
     * @return SiteLock_Logger
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Ensure the logs directory exists and is writable.
     */
    private function ensure_log_dir()
    {
        if (! is_dir($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
        // attempt to create an index.html to prevent directory listing
        $index_file = $this->log_dir . '/index.html';
        if (! file_exists($index_file)) {
            @file_put_contents($index_file, '');
        }
    }

    /**
     * Protect logs folder from web access by creating a .htaccess (Apache).
     * Leaves the file in place if it already exists.
     */
    private function protect_log_dir()
    {
        $htaccess = $this->log_dir . '/.htaccess';
        if (! file_exists($htaccess)) {
            $content = "# Prevent direct web access to log files\n"
                . "<IfModule mod_authz_core.c>\n"
                . "    Require all denied\n"
                . "</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n"
                . "    Order allow,deny\n"
                . "    Deny from all\n"
                . "</IfModule>\n";
            @file_put_contents($htaccess, $content);
        }
    }

    /**
     * Main logging method.
     *
     * Writes a single JSON object per line (JSONL).
     *
     * @param  string $level   e.g., 'error', 'warning', 'info'
     * @param  string $title   short title of error/event
     * @param  string $message detailed message/description
     * @param  array  $context optional associative array of extra data
     * @param  string $class   optional class/name where error occurred
     * @return bool   true on success, false on failure
     */
    public function log($level, $title, $message = '', $context = [], $class = '')
    {
        // normalize
        $level   = (string) $level;
        $title   = (string) $title;
        $message = (string) $message;
        $context = (array) $context;
        $class   = $class ? (string) $class : '';

        // rotate if needed
        $this->rotate_if_needed();

        // build record
        $record = [
            'timestamp' => $this->get_iso_timestamp(),
            'level'     => $level,
            'class'     => $class,
            'title'     => $title,
            'message'   => $message,
            'context'   => $context,
            'user_id'   => get_current_user_id(),
        ];

        $json = wp_json_encode($record); // safe JSON encoding with WP helper

        if (false === $json) {
            // fallback: try json_encode
            $json = json_encode($record, JSON_UNESCAPED_SLASHES);
            if (false === $json) {
                return false;
            }
        }

        // append newline and write to file with exclusive lock
        $line = $json . PHP_EOL;

        $existing = '';
        if ($this->fs->exists($this->log_file)) {
            $existing = $this->fs->get_contents($this->log_file);
            if ($existing === false) {
                $existing = '';
            }
        }

        $ok = $this->fs->put_contents(
            $this->log_file,
            $existing . $line,
            defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644
        );

        return $ok;
    }

    /**
     * Convenience wrapper for error level.
     *
     * @param  string $title
     * @param  string $message
     * @param  array  $context
     * @param  string $class
     * @return bool
     */
    public function error($title, $message = '', $context = [], $class = '')
    {
        return $this->log('error', $title, $message, $context, $class);
    }

    /**
     * Convenience wrapper for info level.
     */
    public function info($title, $message = '', $context = [], $class = '')
    {
        return $this->log('info', $title, $message, $context, $class);
    }

    /**
     * Get current timestamp in ISO 8601 with milliseconds.
     *
     * @return string
     */
    private function get_iso_timestamp()
    {
        $t  = microtime(true);
        $ms = sprintf('%03d', ($t - floor($t)) * 1000);

        return gmdate('Y-m-d\TH:i:s', (int) $t) . '.' . $ms . 'Z';
    }

    /**
     * Rotate log if size exceeds max_size.
     */
    private function rotate_if_needed()
    {
        if (! file_exists($this->log_file)) {
            return;
        }
        clearstatcache(true, $this->log_file);
        $size = filesize($this->log_file);
        if ($size !== false && $size >= $this->max_size) {
            $rotated = $this->log_file . '.' . gmdate('Ymd_His');
            $this->fs->move($this->log_file, $rotated, true);

            // write a new index.html and htaccess for new folder (ensure dir protected)
            $this->ensure_log_dir();
            $this->protect_log_dir();
        }
    }

    /**
     * Change default max size (bytes) for rotation if needed.
     *
     * @param int $bytes
     */
    public function set_max_size($bytes)
    {
        $this->max_size = (int) $bytes;
    }

    /**
     * Get absolute log file path (useful for admin download links).
     *
     * @return string
     */
    public function get_log_file_path()
    {
        return $this->log_file;
    }
}
