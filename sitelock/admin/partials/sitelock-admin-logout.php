<?php

/**
 * Logout redirect
 *
 * @link       http://www.sitelock.com
 * @since      3.0.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */
?>

<div class="sitelock">
        <?php
                if (!$msg) {
                        echo '<p><em>Access has expired. Redirecting to connect option. </em></p>';
                
                        $logout = admin_url() . 'tools.php?page=sitelock&logout=true'; ?>
        
                <script>
                        window.open( "<?php echo esc_url_raw( $logout ); ?>", "_self" );
                </script>
        <?php
                } else {
                        echo '<p><em>'.esc_html( $msg ).'</em></p>';
                }
        ?>
</div>