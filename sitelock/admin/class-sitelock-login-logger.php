<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sitelock_Login_Logger
{
    public const LOG_TABLE                      = 'sitelock_login_logs';
    public const OPTION_SITELOCK_ENABLED_ROLES  = 'sitelock_login_logger_roles';
    public const OPTION_SITELOCK_RETENTION_DAYS = 'sitelock_login_logger_retention';

    public function __construct()
    {
        add_action('wp_login', [$this, 'log_successful_login'], 10, 2);
        add_action('wp_login_failed', [$this, 'log_failed_login']);
        add_action('admin_init', [$this, 'maybe_schedule_cron']);
        add_action('sitelock_login_log_cleanup_cron', [$this, 'purge_old_logs']);
    }

    public static function on_activation()
    {
        global $wpdb;
        $table           = $wpdb->prefix . self::LOG_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED,
            roles TEXT,
            ip_address VARCHAR(45),
            logged_at DATETIME,
            status ENUM('success', 'failure') DEFAULT 'success',
            user_agent TEXT,
            INDEX (user_id),
            INDEX (logged_at),
            INDEX (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Default retention
        if (!get_option(self::OPTION_SITELOCK_RETENTION_DAYS)) {
            update_option(self::OPTION_SITELOCK_RETENTION_DAYS, 7);
        }
    }

    public static function on_deactivation()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::LOG_TABLE;

        // Validate table name manually to prevent injection
        if (preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
            $table_name_escaped = esc_sql($table_name);
            $sql                = "DROP TABLE IF EXISTS `$table_name_escaped`";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe direct query to remove custom table
            $result = $wpdb->query($sql);

            if ($result === false) {
                sitelock_log(
                    'error',
                    'Table Deletion Failed',
                    'Failed to drop table during plugin deactivation.',
                    ['table_name' => $table_name, 'db_error' => $wpdb->last_error],
                    __CLASS__
                );
            }
        }

        // Clean up options
        delete_option(self::OPTION_SITELOCK_RETENTION_DAYS);
        wp_clear_scheduled_hook('sitelock_login_log_cleanup_cron');
    }

    public function maybe_schedule_cron()
    {
        if (!wp_next_scheduled('sitelock_login_log_cleanup_cron')) {
            wp_schedule_event(time(), 'hourly', 'sitelock_login_log_cleanup_cron');
        }
    }

    public function log_successful_login($user_login, $user)
    {
        if (!$user instanceof WP_User) {
            return;
        }
        $sitelock_enabled_roles = ($tmp = get_option(self::OPTION_SITELOCK_ENABLED_ROLES, [])) && is_array($tmp) ? $tmp : [];
        $user_roles             = $user->roles;

        if (empty(array_intersect($sitelock_enabled_roles, $user_roles))) {
            return; // Role not enabled
        }

        $this->insert_log($user->ID, $user_roles, $this->get_ip(), 'success');
    }

    public function log_failed_login($username)
    {
        $user = get_user_by('login', $username);

        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            return;
        }

        $sitelock_enabled_roles = ($tmp = get_option(self::OPTION_SITELOCK_ENABLED_ROLES, [])) && is_array($tmp) ? $tmp : [];
        $user_roles             = $user->roles;

        // If no roles are enabled or the user has no roles, skip logging
        if (empty(array_intersect($sitelock_enabled_roles, $user_roles))) {
            return;
        }

        $this->insert_log($user->ID, $user_roles, $this->get_ip(), 'failure');
    }

    private function insert_log($user_id, $roles, $ip, $status)
    {
        global $wpdb;
        $table      = $wpdb->prefix . self::LOG_TABLE;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'unknown';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Safe use of $wpdb->insert for custom table
        $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'roles'      => maybe_serialize($roles),
                'ip_address' => $ip,
                'logged_at'  => current_time('mysql'),
                'status'     => $status,
                'user_agent' => $user_agent,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }

    public function purge_old_logs()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::LOG_TABLE;

        // Validate table name manually to prevent SQL injection
        if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            $table = esc_sql($table); // Sanitize table name
            $days  = (int) get_option(self::OPTION_SITELOCK_RETENTION_DAYS, 7);

            if ($days > 0) {
                $date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe direct query to remove custom table
                $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE logged_at < %s", $date));
            }
        }
    }

    private function get_ip()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return sanitize_text_field(wp_unslash($_SERVER[$key]));
            }
        }

        return '0.0.0.0';
    }
}
