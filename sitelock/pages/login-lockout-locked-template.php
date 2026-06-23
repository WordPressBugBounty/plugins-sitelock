<?php
if (!defined('ABSPATH')) 
{
    exit;
}

?>
<div class="sitelock-card">
    <header class="sitelock-card__head">
        <h1 id="sitelock-title" class="sitelock-lockout-title">Locked Temporarily</h1>
    </header>
    <div class="sitelock-card__body text-center">
        <div class="sitelock-lockout-error-notice">
            <p>You have attempted to login with an incorrect combination of a username and/or password too many times. You may try again in  <?php echo esc_html(get_option('sitelock_login_lockout_duration', 30)); ?> minutes.</p>
        </div>
        <div class="sitelock-timer-section">
            <p>Time remaining:
                <strong id="sitelock-countdown" aria-live="polite" aria-atomic="true">
                    <?php echo esc_html(sprintf('%02d:%02d:%02d', floor($time_remaining / 3600), floor(($time_remaining % 3600) / 60), $time_remaining % 60)); ?>
                </strong>
            </p>
        </div>
        <div class="sitelock-actions" id="sitelock-retry-container">
            <button type="button" class="sitelock-btn" id="sitelock-retry-btn" onclick="window.location.href='<?php echo esc_url( wp_login_url() ); ?>';">Try Again</button>
        </div>


    </div>
    <footer class="sitelock-card__foot ">
        <p class="sitelock-card-powered-by-text">Powered by
            <a target="_blank" rel="noopener noreferrer" class="footer-sitelock-text" href="https://wordpress.org/plugins/sitelock/">
                SiteLock WP Security
            </a>
        </p>
    </footer>
</div>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        var remaining = <?php echo (int) $time_remaining; ?>;
        var display = document.getElementById("sitelock-countdown");
        var retryContainer = document.getElementById("sitelock-retry-container");

        function formatTime(seconds) {
            var m = Math.floor(seconds / 60);
            var s = seconds % 60;
        var h = Math.floor(seconds / 3600);
        seconds %= 3600;
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        return (h < 10 ? "0" : "") + h + ":" + (m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s;
        }

        var timer = setInterval(function() {
            remaining--;

            if (remaining <= 0) {
                clearInterval(timer);
                remaining = 0;
                if (retryContainer) {
                    retryContainer.style.display = "block";
                }
            }

            if (display) {
                display.textContent = formatTime(remaining);
            }
        }, 1000);
    });
</script>