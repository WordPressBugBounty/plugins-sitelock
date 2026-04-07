<?php
$sitelock_site_id = get_option('sitelock_site_id', '');
?>

<div id="connection-to-sitelock"
    class="hidden simple-box border-[#DDDDDD] bg-[#fff] mb-5 p-8 min-h-full xl:min-h-[500px]">
    <div class="max-w-[640px]">
        <h3 class="box-title mb-4">SiteLock License</h3>
        <?php if (!$sitelock_connection_status) { ?>
        <p class="text-[14px] font-normal leading-[150%] mb-5">
            You can find your SiteLock License Key by logging into your
            <a href="<?php echo esc_url(sitelock_api_url()) ?>" class="!inline-block underline text-[#2161cc]" target="_blank" rel="noopener noreferrer">SiteLock Dashboard</a>.
            <br>If you don’t have a SiteLock account,
            <a href="<?php echo esc_url(sitelock_get_redirect_url('signup') . admin_url()); ?>" class="!inline-block underline text-[#2161cc]" target="_blank" rel="noopener noreferrer">sign up here</a>
            for a free or paid plan to get started.
        </p>
        <?php } ?>
        <div class="mb-7 pt-3">
            <p class="tab-content-field-title mb-1">License key</p>
            <form method="post"
                action="<?php echo esc_url(admin_url() . 'admin-post.php?action=handle_auth_key') ?>">
                <?php wp_nonce_field('sitelock_license_key_action', 'sitelock_license_key_nonce'); ?>
                <div class="w-full mb-5">
                    <input type="text" name="sitelock_license_key" class="w-[480px] h-[36px] rounded mb-2"
                        value="<?php echo esc_attr(get_option('sitelock_license_key', '')) ?>" />
                        <?php if (empty(get_option('sitelock_license_key', ''))) { ?>
                            <p class="tab-content-field-content text-[#6A6A6A]">Enter your SiteLock License Key here.</p>
                        <?php } ?>
                </div>
                <div class="flex items-center gap-4">
                    <?php if (!$sitelock_connection_status) { ?>
                        <button class="w-[136px] h-[32px] btn-primary"
                            type="submit">Save Changes</button>
                        <a target="_blank" rel="noopener noreferrer" class="w-[136px] h-[32px] btn-secondary"
                            href="<?php echo esc_url(sitelock_api_url() . '/login?redirect_to=site.settings.wordpress&siteId=' . sitelock_get_site_identifier()); ?>">Get License Key</a>
                    <?php } else { ?>
                        <a class="w-[191px] h-[32px] btn-primary"
                            href="<?php echo esc_url(admin_url('admin.php?page=sitelock')) ?>">Go to SiteLock Dashboard</a>
                        <a class="w-[132px] h-[32px] btn-secondary"
                            href="<?php echo esc_url(admin_url() . 'admin.php?page=sitelock-settings&tab=connection-to-sitelock&logout=true&_wpnonce=' . wp_create_nonce('sitelock_logout_action')) ?>">Disconnect</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const licenseKeyInput = document.querySelector('input[name="sitelock_license_key"]');
        const saveChangesButton = document.querySelector('button[type="submit"]');

        function toggleButtonState() {
            if (licenseKeyInput.value.trim() === '') {
                saveChangesButton.setAttribute('disabled', 'disabled');
                saveChangesButton.classList.add('bg-[#DDDDDD]', 'cursor-not-allowed', 'text-[#6A6A6A]');
            } else {
                saveChangesButton.removeAttribute('disabled');
                saveChangesButton.classList.remove('bg-[#DDDDDD]', 'cursor-not-allowed', 'text-[#6A6A6A]');
                saveChangesButton.classList.add('bg-[#2D68C4]', 'text-[#fff]', 'hover:text-[#fff]');
            }
        }

        // Initialize button state on page load
        toggleButtonState();

        // Add event listener to input field
        licenseKeyInput.addEventListener('input', toggleButtonState);
    });
</script>
