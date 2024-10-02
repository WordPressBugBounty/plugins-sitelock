<?php

/**
 * Display for edit page meta box
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */

?>
<div class="sitelock">
    
    <!-- Scan Results -->
    <div class="row">
        <div class="span1">
            <div class="sitelock_scan_results">
                <?php 
                $rules = array(
                    'a' => array( 'href' => array(), 'target' => array() ),
                    'div' => array( 'style' => array(), 'class' => array() ),
                );
                echo wp_kses( $scan_results, $rules ); ?>
            </div>
        </div>
        <div class="span11">
            <?php if ( $status_check === 'yellow' ) { ?>
                <em>Scan pending</em>
            <?php } else { ?>
                Last <?php echo esc_attr( $scan_result ); ?>scan - <?php echo esc_html( $last_scanned ); ?>
            <?php } ?>
        </div>
    </div>

    <div class="clearfix"></div>

    <!-- WAF Options -->
    <?php if ( $has_waf ) : ?>

        <hr />
        <div class="row">
            <div class="span8 padding-top-5">
                <strong>Page Protect</strong>
            </div>
            <div class="span4">
                <select name="sitelock_page_protect" class="page_protect_option" class="pull-right">
                    <option value="off">Off</option>
                    <option value="on"<?php echo $page_protect == 'on' ? ' selected' : ''; ?>>On</option>
                </select>
                <input type="hidden" name="sitelock_page_protect_current" value="<?php echo esc_attr( $page_protect ); ?>" />
                <div class="clearfix"></div>
            </div>
            
        </div>

    <?php endif; ?>
    
    <hr />
    
    <a target="_blank" href="<?php echo esc_url_raw( $admin_url ); ?>&scan=<?php echo esc_attr( $malware_scan_code ); ?>">
        <span data-type="1" class="dashicons dashicons-update sitelock-security-scan-now"></span>Rescan
    </a>

</div>
