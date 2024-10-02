<?php

/**
 * Dashboard widget display
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */
?>

<?php
    $rules = array(
        'a' => array( 'href' => array(), 'target' => array() ),
        'div' => array( 'style' => array(), 'class' => array() ),
    );
?>
<div class="sitelock">
    
    <!-- Subscription Status -->
    <div class="row">
        <div class="span1">
            <?php echo wp_kses( sitelock_decorate_scan_results( ( strtolower( $this->site_parent_data[ 'status' ] ) == 'active' ? true : false ) ), $rules ); ?>
        </div>
        <div class="span11">
            <strong>Subscription Status</strong>
        </div>
    </div>
    
    <hr />
        
    <!-- Security Status -->
    <div class="row">
        <div class="span1">
            <?php echo wp_kses( sitelock_decorate_scan_results( $this->site_parent_data[ 'attention_flag' ] ), $rules ); ?>
        </div>
        <div class="span11">
            <strong>Security Status</strong>
        </div>
    </div>

    <hr />
    
    <!-- View More -->
    <a href="<?php echo esc_url_raw( admin_url( 'tools.php?page=sitelock' ) ); ?>">View More Details</a>
</div>
