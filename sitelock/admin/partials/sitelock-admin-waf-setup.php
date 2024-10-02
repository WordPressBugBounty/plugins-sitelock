<?php

/**
 * WAF Setup 
 *
 * @link       http://www.sitelock.com
 * @since      2.0.0
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin/partials
 */

?>
    
<div class="wrap">
<!--[if lt IE 7]>  <div class="ie ie6 lte9 lte8 lte7"> <![endif]-->
<!--[if IE 7]>     <div class="ie ie7 lte9 lte8 lte7"> <![endif]-->
<!--[if IE 8]>     <div class="ie ie8 lte9 lte8"> <![endif]-->
<!--[if IE 9]>     <div class="ie ie9 lte9"> <![endif]-->
<!--[if gt IE 9]>  <div> <![endif]-->
<!--[if !IE]><!--> <div>             <!--<![endif]-->
    <div class="sitelock">
        <div class="wpslp_container">
            <div class="row">
                <div class="span12">
                    
                    <div class="wpslp_summary_box">
                        <h2>TrueShield Setup</h2><br />
                        <p><a href="<?php echo esc_url_raw( sitelock_sso( 'trueshield/wizard/waf' ) ); ?>" target="_blank">Click here</a> to enter the TrueShield setup wizard.</p>
                    </div>
                         
                        
                           
                </div><!-- /.span12 -->
            </div><!-- /.row -->
        </div><!-- /.wpslp_container -->
    </div><!-- /.sitelock -->
</div><!-- /ie check -->
</div><!-- /.wrap -->