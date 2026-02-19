<?php
// expects $data['lockout_remaining'], $data['site_name'], $data['logo']
if (!defined('ABSPATH')) 
{
    exit;
}
$sitelock_language_tokens = sitelock_get_language_tokens();

include __DIR__ . '/partials/header.php';

// Default to 60 minutes if not set (fallback)
$remaining_seconds = isset($data['lockout_remaining']) ? (int)$data['lockout_remaining'] : 3600;
$lockout_period = isset($data['lockout_period']) ? (int)$data['lockout_period'] : 3600;
?>

<header class="sitelock-card__head">
    <h1 id="sitelock-title" class="sitelock-2fa-lockout-title" >
        <?php esc_html_e('Account Locked', 'sitelock-wordpress-plugin'); ?><br>
        <?php esc_html_e('Temporarily', 'sitelock-wordpress-plugin'); ?>
    </h1>
</header>

<div class="sitelock-card__body">
    <div class="sitelock-2fa-lockout-error-notice" >
        <p class="sitelock-2fa-lockout-error-notice-p1" >
            <?php 
            /* translators: %s: lockout period in minutes */
            // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
            echo wp_kses_post(sprintf(__('You have entered <strong>too many invalid codes</strong>.', 'sitelock-wordpress-plugin'))); 
            ?>
        </p>
        <p class="sitelock-2fa-lockout-error-notice-p2">
            <?php 
            /* translators: %d: lockout period in minutes */
            // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
            echo wp_kses_post(sprintf(__('For your security, access to your account is <strong>temporarily locked for %d minutes</strong>. You will be able to try again after this time has passed.', 'sitelock-wordpress-plugin'), $lockout_period)); 
            ?>
        </p>
    </div>

    <div class="sitelock-timer-section">
        <p>
            <?php esc_html_e('Time remaining:', 'sitelock-wordpress-plugin'); ?> 
            <strong id="sitelock-countdown">
                <?php echo esc_html(gmdate('i:s', $remaining_seconds)); ?>
            </strong>
        </p>
    </div>

    <div class="sitelock-actions" id="sitelock-retry-container">
        <button type="button" class="sitelock-btn" id="sitelock-retry-btn" onclick="location.reload();">
            <?php esc_html_e('Try Again', 'sitelock-wordpress-plugin'); ?>
        </button>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var remaining = <?php echo (int)$remaining_seconds; ?>;
        var display = document.getElementById('sitelock-countdown');
        var retryContainer = document.getElementById('sitelock-retry-container');
        
        function formatTime(seconds) {
            var m = Math.floor(seconds / 60);
            var s = seconds % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        var timer = setInterval(function() {
            remaining--;
            
            if (remaining <= 0) {
                clearInterval(timer);
                remaining = 0;
                if (retryContainer) {
                    retryContainer.style.display = 'flex';
                }
            }
            
            if (display) {
                display.textContent = formatTime(remaining);
            }
        }, 1000);
    });
</script>

<?php
include __DIR__ . '/partials/footer.php';
?>
