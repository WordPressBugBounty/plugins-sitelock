<?php

/**
 * Connect display
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */
?>

<div class="sitelock">
    <div class="wpslp_container">

        <h2>SiteLock Security</h2>

        <?php if ( isset( $_GET[ 'exceeded' ] ) ) : ?>
            <div id="message" class="error notice notice-error is-dismissible below-h2">
                <p>
                    API usage exceeded.  Please wait 1 hour before using again.
                </p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>
        <?php endif; ?>
        
        <br />

        <div class="row">
            <div class="span8">
                
                <div class="postbox metabox-holder">
                    <div class="inside">
<!--                        
                        <h4>Please connect your SiteLock account with this install of WordPress.</h4>-->

                        <form action="<?php echo esc_url_raw( sitelock_api_url(), array( 'https' ) ); ?>/login.php" method="post" style="float: left; margin: 0 15px 0 0;">
                            <input type="hidden" name="return_to_url" value="<?php echo esc_url_raw( admin_url() . 'admin-post.php?action=handle_auth_key' ); ?>" />
                            <input type="hidden" name="plugin_agent" value="true" />
                            <input type="hidden" name="secret" value="<?php echo esc_attr( $this->api->get_secret() ); ?>" />
                            <input type="hidden" name="v" value="<?php echo esc_attr( $this->version ); ?>" />
                            <input type="submit" value="Connect to SiteLock" class="button-primary button-hero"/>
                        </form> 

                        <ul>
                            <li> &bull; Have a SiteLock account? Sign in directly or via a partner.</li>
                            <li> &bull; New to SiteLock? Establish an account.</li>
                        </ul>

                        <div class="clearfix"></div>
                    </div>
                </div>
                
            </div>
        </div>

    </div>
</div>

