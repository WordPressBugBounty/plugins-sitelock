<?php
/**
 * Class SiteLock_Admin_Monitor
 * Monitors unexpected admin additions/removals and logs them.
 */
class SiteLock_Admin_Monitor
{
    public const OPTION_SNAPSHOT_KEY = 'sitelock_admin_snapshot';
    public const LOG_TABLE           = 'sitelock_admin_logs';
    public function __construct()
    {
        add_action('init', [$this, 'maybe_schedule_cron']);
        add_action('sitelock_check_admins_cron', [$this, 'check_admin_users']);

        add_action('user_register', [$this, 'log_user_addition'], 10, 1);
        add_action('set_user_role', [$this, 'log_role_change'], 10, 3);
        add_action('delete_user', [$this, 'log_user_deletion'], 10, 3);
    }

    public static function on_activation()
    {
        global $wpdb;
        $table_name      = $wpdb->prefix . self::LOG_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            user_email VARCHAR(255),
            username VARCHAR(100),
            action VARCHAR(100),
            role_before VARCHAR(100),
            role_after VARCHAR(100),
            context VARCHAR(100),
            is_suspicious BOOLEAN DEFAULT 0,
            details TEXT,
            caller_file VARCHAR(255),
            logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Save initial snapshot
        update_option(self::OPTION_SNAPSHOT_KEY, self::get_current_admin_ids());

        // Schedule cron
        if (!wp_next_scheduled('sitelock_check_admins_cron')) {
            wp_schedule_event(current_time('timestamp'), 'hourly', 'sitelock_check_admins_cron');
        }
    }

    public static function on_deactivation()
    {
        global $wpdb;

        $table_name = esc_sql($wpdb->prefix . self::LOG_TABLE);

        // Use caching to avoid redundant database calls
        $cache_key   = "drop_table_{$table_name}";
        $cache_group = 'sitelock_admin_monitor';

        // Check if the result is already cached
        $result = wp_cache_get($cache_key, $cache_group);

        if ($result === false) {
            $sql = "DROP TABLE IF EXISTS `{$table_name}`";
            // Use $wpdb->query() for DROP TABLE operations
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($sql);
            $result = true; // Assume success for caching purposes

            // Cache the result for future use
            wp_cache_set($cache_key, $result, $cache_group, HOUR_IN_SECONDS);
        }

        if ($result === false) {
            sitelock_log(
                'error',
                'Table Drop Failed',
                "Failed to drop table {$table_name}: {$wpdb->last_error}",
                ['table_name' => $table_name, 'db_error' => $wpdb->last_error],
                __CLASS__
            );
        }

        delete_option(self::OPTION_SNAPSHOT_KEY);
        wp_clear_scheduled_hook('sitelock_check_admins_cron');
    }

    private static function get_current_admin_ids()
    {
        $admins = get_users(['role' => 'administrator', 'fields' => ['ID']]);

        return array_map('intval', wp_list_pluck($admins, 'ID'));
    }

    public function maybe_schedule_cron()
    {
        if (!wp_next_scheduled('sitelock_check_admins_cron')) {
            wp_schedule_event(time(), 'hourly', 'sitelock_check_admins_cron');
        }
    }

    public function check_admin_users()
    {
        $previous = get_option(self::OPTION_SNAPSHOT_KEY, []);
        $current  = $this->get_current_admin_ids();

        $added   = array_diff($current, $previous);
        $removed = array_diff($previous, $current);

        $cutoff_time = gmdate('Y-m-d H:i:s', strtotime('-60 minutes'));
        // Check added admin users
        foreach ($added as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                // Skip if already logged in last 60 mins
                $exists = $this->log_exists_recently($user_id, $cutoff_time);

                if ($exists == 0) {
                    $this->log_event($user, 'added_admin', null, 'administrator', true, 'cron');
                }
            }
        }

        // Check removed admin users
        foreach ($removed as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $exists   = $this->log_exists_recently($user_id, $cutoff_time, 'role_changed');
                $roles    = $user->roles;
                $new_role = $roles[0] ?? null;
                if ($exists == 0) {
                    $this->log_event($user, 'role_changed', 'administrator', $new_role, true, 'cron');
                }
            } else {
                $exists = $this->log_exists_recently($user_id, $cutoff_time, 'user_deleted');
                // User may have been deleted from DB — log basic info if needed
                if ($exists == 0) {
                    $user_obj = (object)[
                        'ID'         => $user_id,
                        'user_login' => 'unknown',
                        'user_email' => '',
                    ];
                    $this->log_event($user_obj, 'removed_admin', 'administrator', null, true, 'cron');
                }
            }
        }

        // Update the snapshot
        update_option(self::OPTION_SNAPSHOT_KEY, $current);
    }

    public function log_exists_recently($user_id, $cutoff_time, $action = null)
    {
        global $wpdb;

        // Validate inputs
        $user_id = intval($user_id);
        if (!$user_id || !strtotime($cutoff_time)) {
            return 0;
        }

        // Normalize/sanitize action
        if ($action !== null) {
            $action = trim(sanitize_text_field($action));
            if ($action === '') {
                $action = null;
            }
        }

        // Create a unique cache key (include action if provided)
        $cache_key_parts = ['log_exists_recently', $user_id, md5($cutoff_time)];
        if ($action !== null) {
            $cache_key_parts[] = md5($action);
        }
        $cache_key   = implode('_', $cache_key_parts);
        $cache_group = 'exist_log_check';

        // Try to get cached result
        $cached_result = wp_cache_get($cache_key, $cache_group);
        if ($cached_result !== false) {
            return intval($cached_result);
        }

        // Prepare and execute query
        $table_name = esc_sql($wpdb->prefix . self::LOG_TABLE); // Sanitize table name

        if ($action === null) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $query = $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND logged_at >= %s", $user_id, $cutoff_time);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $query = $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND logged_at >= %s AND action = %s", $user_id, $cutoff_time, $action);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = intval($wpdb->get_var($query));

        // Cache the result
        wp_cache_set($cache_key, $result, $cache_group, HOUR_IN_SECONDS);

        return $result;
    }

    public function log_user_addition($user_id)
    {
        $user        = get_userdata($user_id);
        $caller_file = $this->get_caller_file();
        if (in_array('administrator', $user->roles)) {
            $cutoff_time = gmdate('Y-m-d H:i:s', strtotime('-60 minutes'));
            if ($this->log_exists_recently($user_id, $cutoff_time) == 0) {
                $this->log_event(
                    $user,
                    'user_created',
                    null,
                    'administrator',
                    $this->is_suspicious($user),
                    $caller_file
                );
            } else {
                // Update existing log entry from role_changed to user_created
                global $wpdb;
                $table = $wpdb->prefix . self::LOG_TABLE;
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $table,
                    ['action'  => 'user_created'],
                    ['user_id' => $user_id, 'action' => 'role_changed'],
                    ['%s'],
                    ['%d', '%s']
                );
            }
        }
    }

    public function log_role_change($user_id, $new_role, $old_roles)
    {
        if ($new_role === 'administrator' || in_array('administrator', $old_roles)) {
            $user = get_userdata($user_id);

            $caller_file = $this->get_caller_file();

            $this->log_event(
                $user,
                'role_changed',
                implode(',', $old_roles),
                $new_role,
                $this->is_suspicious($user),
                $caller_file
            );
        }
    }

    public function log_user_deletion($user_id, $reassign, $user)
    {
        if ($user && in_array('administrator', $user->roles)) {
            $caller_file = $this->get_caller_file();
            $this->log_event(
                $user,
                'user_deleted',
                'administrator',
                null,
                $this->is_suspicious($user),
                $caller_file
            );
        }
    }

    private function is_suspicious($user)
    {
        $email              = $user->user_email;
        $suspicious_domains = ['mailinator.com', 'tempmail', 'fake', 'test'];
        foreach ($suspicious_domains as $pattern) {
            if (stripos($email, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function detect_context()
    {
        if (defined('WP_CLI') && WP_CLI) {
            return 'WP-CLI';
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return 'AJAX';
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return 'REST';
        }
        if (is_admin()) {
            return 'Admin UI';
        }

        return 'Unknown';
    }

    private function get_caller_file()
    {
        // Used intentionally to detect caller file in production-safe way
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        // $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        // foreach ($backtrace as $trace) {
        //     if (isset($trace['file']) && strpos($trace['file'], ABSPATH) === 0) {
        //         if (strpos($trace['file'], plugin_dir_path(__FILE__)) === false) {
        //             if (strpos($trace['file'], ABSPATH . 'wp-includes') === 0 || strpos($trace['file'], ABSPATH . 'wp-content/plugins') === 0) {
        //                 continue;
        //             }
        //             return str_replace(ABSPATH, '', $trace['file']);
        //         }
        //     }
        // }
        return 'unknown';
    }

    private function log_event($user, $action, $old_role = null, $new_role = null, $is_suspicious = false, $caller_file = 'unknown')
    {
        global $wpdb;
        $table   = $wpdb->prefix . self::LOG_TABLE;
        $action  = sanitize_text_field($action);
        $details = json_encode([
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'unknown',
        ]);

        // Check for "role_changed" action in "Admin UI" context
        if ($action === 'user_created') {
            // Create a unique cache key
            $cache_key   = "existing_entry_id_{$user->ID}_role_changed";
            $cache_group = 'sitelock_admin_monitor';

            // Try to get cached result
            $existing_entry_id = wp_cache_get($cache_key, $cache_group);

            if ($existing_entry_id === false) {
                // Sanitize table name
                $table_name = esc_sql($table);

                // Prepare and execute query
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $existing_entry_id = $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT id FROM %s WHERE user_id = %d AND action = %s',
                        $table_name,
                        $user->ID,
                        'role_changed'
                    )
                );

                // Cache the result
                wp_cache_set($cache_key, $existing_entry_id, $cache_group, HOUR_IN_SECONDS);
            }
            if ($existing_entry_id) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $table,
                    ['action' => 'user_created'],
                    ['id'     => $existing_entry_id],
                    ['%s'],
                    ['%d']
                );

                return; // Skip inserting a new log entry
            }
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($table, [
            'user_id'       => $user->ID,
            'user_email'    => $user->user_email,
            'username'      => $user->user_login,
            'action'        => $action,
            'role_before'   => $old_role,
            'role_after'    => $new_role,
            'context'       => ($context = $this->detect_context()) === 'Unknown' && $caller_file === 'cron' ? 'Cron' : ($context ?? 'Unknown'),
            'is_suspicious' => $is_suspicious ? 1 : 0,
            'details'       => $details,
            'caller_file'   => $caller_file,
            'logged_at'     => current_time('mysql'),
        ]);
        if ($result === false) {
            sitelock_log(
                'error',
                'Log Event Insertion Failed',
                "Failed to insert log event into table {$table}: {$wpdb->last_error}",
                ['table_name' => $table, 'db_error' => $wpdb->last_error],
                __CLASS__
            );
        }
    }
}
