<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
            <div class="sitelock-footer pt-6 mt-4 inline-flex border-0 text-[13px]">
                <?php esc_html_e('Powered by', 'sitelock-wordpress-plugin'); ?>&nbsp; <a href="https://www.sitelock.com" target="_blank" class="text-gray-600 hover:text-blue-600 underline">SiteLock WP Security</a>
            </div>
        </div>
    </div>
    <?php 
    if (isset($extra_footer)) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This variable contains trusted HTML/Scripts constructed internally.
        echo $extra_footer;
    }
    // Manual script inclusion
    wp_print_scripts(['sitelock-2fa-setup-js']);
    ?>
</body>
</html>
