<div id="sitelock-2fa-warning" class="notice notice-warning"
    style="margin-top: 20px; padding: 10px; border-left: 4px solid #ffba00; background: #fffbe6;">
    <p><strong><?php echo esc_html($message) ?></strong></p>
</div>

<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        setTimeout(function () {
            var notice = document.getElementById("sitelock-2fa-warning");
            var appPasswordsSection = document.getElementById("application-passwords-section");
            if (notice && appPasswordsSection) {
                appPasswordsSection.parentNode.insertBefore(notice, appPasswordsSection);
            }
        }, 500);
    });
</script>