<?php
// expects $data['error'], $data['site_name'], $data['logo']
if (!defined('ABSPATH')) exit;
$sitelock_language_tokens = sitelock_get_language_tokens();

$sitelock_site_id = sanitize_key(get_option('sitelock_site_id', ""));

include __DIR__ . '/partials/header.php';
?>
            <header class="sitelock-card__head">           
                <h1 id="sitelock-title"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication']['title']); ?></h1>
                <?php if (!empty($data['sitelock_2fa_authenticate_error'])): ?>
                    <div class="sitelock-error" role="alert" aria-live="assertive">
                        <?php echo wp_kses_post($data['sitelock_2fa_authenticate_error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($data['sitelock_2fa_recovery_error'])): ?>
                    <div class="sitelock-error" role="alert" aria-live="assertive">
                        <?php echo wp_kses_post($data['sitelock_2fa_recovery_error']); ?>
                    </div>
                <?php endif; ?>
                <?php
                $sitelock_verify_is_recovery = !empty($data['sitelock_2fa_recovery_error']);
                $sitelock_verify_totp_class  = $sitelock_verify_is_recovery ? 'hidden' : '';
                $sitelock_verify_rec_class   = $sitelock_verify_is_recovery ? '' : 'hidden';
                ?>
                <p class="sitelock-sub <?php echo esc_attr($sitelock_verify_totp_class); ?>" id="sitelock-2fa-text"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication']['totpText']); ?></p>
                <p class="sitelock-sub <?php echo esc_attr($sitelock_verify_rec_class); ?>" id="sitelock-recovery-text"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication']['backupCodeText']); ?></p>
            </header>

            <div class="sitelock-card__body">
               
                <?php
                // Preserve existing query parameters
                 // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Processing form data without nonce verification.
                $sitelock_verify_action_url = add_query_arg($_GET, sitelock_build_url_with_query_params(add_query_arg('action', 'sitelock-2fa', wp_login_url())));
                ?>
                <form id="sitelock-2fa-form" action="<?php echo esc_url($sitelock_verify_action_url); ?>" method="post" novalidate>
                    <?php wp_nonce_field('sitelock_2fa_verify', 'sitelock_2fa_nonce'); ?>

                    <div class="sitelock-field -mt-8 <?php echo esc_attr($sitelock_verify_totp_class); ?>" id="totp-group">
                        <fieldset class="sitelock-2fa-fieldset">
                            <legend class="sitelock-2fa-label"></legend>
                            <div class="sitelock-2fa-inputs">
                                <?php for ($sitelock_verify_index = 1; $sitelock_verify_index <= 6; $sitelock_verify_index++): ?>
                                    <input
                                        type="text"
                                        id="totp_code_<?php echo esc_attr($sitelock_verify_index); ?>"
                                        maxlength="1"
                                        inputmode="numeric"
                                        pattern="\d"
                                        class="sitelock-input sitelock-2fa-box"
                                        required
                                        aria-required="true"
                                        aria-label="Digit <?php echo esc_attr($sitelock_verify_index); ?> of 6"
                                    />
                                <?php endfor; ?>
                            </div>

                            <input type="hidden" id="totp_code" name="totp_code" value="" />
                        </fieldset>
                    </div>
                   
                    <div class="sitelock-recovery-field -mt-8 <?php echo esc_attr($sitelock_verify_rec_class); ?>" id="recovery-group">
                        <?php if ($sitelock_verify_is_recovery): ?>
                            <span class="sitelock-recovery-error hidden"></span>
                        <?php endif; ?>                      
                        <input 
                            id="recovery_code" 
                            name="recovery_code" 
                            type="text" 
                            class="sitelock-recovery-input" 
                            placeholder="XXXX XXXX XXXX XXXX" 
                            maxlength="19" 
                            pattern="\d{4}\s\d{4}\s\d{4}\s\d{4}" 
                            title="Please enter a 16-digit code in the format XXXX XXXX XXXX XXXX" 
                            required 
                            aria-required="true" 
                            oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, '').replace(/(\w{4})(?!\s|$)/g, '$1 ').trim();" 
                        />
                    </div>

                    <div class="sitelock-help">
                        <a href="#" id="sitelock-toggle-recovery" class="sitelock-link"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication']['useBackupCode']); ?></a>
                    </div>

                    <div class="sitelock-actions">
                        <button type="submit" class="sitelock-btn" id="sitelock-submit"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication']['verifyText']); ?></button>
                    </div>
                </form>
            </div>

<?php
include __DIR__ . '/partials/footer.php';
?>