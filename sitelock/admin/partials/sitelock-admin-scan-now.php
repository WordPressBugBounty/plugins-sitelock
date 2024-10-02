<?php

/**
 * Scan Results Message
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */
?>

    <div class="sitelock">
    <p><br /></p>
    <?php

        // response from api   
        ?>
        <div id="message" class="updated notice notice-success is-dismissible below-h2">
            <p><?php 
                echo esc_html( !empty( $scan_results[ 'error' ] ) ? 
                    $scan_results[ 'error' ][ 'message' ] : 
                    ( $scan_results[ 'message' ] == 'Scan Queued Successfully.' ? 
                        'Scan queued successfully, please allow an hour and check back periodically.' : 
                        $scan_results[ 'message' ] ) );
                ?> (<small>redirecting...</small>)</p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div> 
        <?php
        
    ?>
    <script>
        
        setTimeout( function() {
            window.location.href = '<?php echo esc_url_raw( $admin_url ); ?>';
        }, 5000 );
        
    </script>

</div>
