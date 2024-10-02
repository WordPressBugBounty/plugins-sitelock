<?php

/**
 * WAF Configuration settings
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
                        <h2>TrueShield Configuration</h2><br />
                    </div>
                    
                    <?php
                    
                        if ( $status != '' )
                        {
                            ?>
                            <div id="message" class="success notice notice-success is-dismissible below-h2">
                                <p> 
                                    <?php echo esc_html( $status ); ?>
                                </p>
                                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                            </div>
                            <?php
                        }
                        else if ( $error != '' )
                        {
                            ?>
                            <div id="message" class="error notice notice-error is-dismissible below-h2">
                                <p> 
                                    <?php echo esc_html( $error ); ?>
                                </p>
                                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                            </div>
                            <?php
                        }
                        
                    ?>
                    
                    
                    <!--
                    <h5>Admin Login Page Protect<select name=""><option value="on">On</option><option value="off">Off</option></select></h5>
                    <h5>CDN/WAF Activation (Current TrueShield Wizard) </h5>
                    -->
                    
                    <div class="postbox metabox-holder">
                        <h3><span>Trafic Routing</span></h3>
                        <div class="inside">
                    
                            <div class="row">
                                <div class="span4">
                                    <p>If you need to temporarily route your traffic away from SiteLock's CDN then choose the last option in the list to the right. Otherwise choose the first option.</p>
                                    <p>The first option has two sub-options which help increase speed and security.</p>
                                </div>
                                <div class="span8">
                                    
                                    <form method="post">
                                        <div class="list-group div-check-options">
                                            
                                            <label>
                                                <input type="radio" id="bypass" name="bypass" value="0"<?php echo $general_setting[ 'bypass' ] == '0' ? ' checked' : ''; ?> /> Route my traffic via SiteLock's network
                                            </label>
                                            
                                            <div class="sitelock_true_options <?php echo $general_setting[ 'bypass' ] == '0' ? '' : 'hide-on-load'; ?>">
                                                
                                                <div>
                                                    <label>
                                                        <input type="checkbox" id="use_true_speed" name="use_true_speed" value="1"<?php echo $general_setting[ 'use_true_speed' ] == '1' ? ' checked' : ''; ?> /> Use TrueSpeed for acceleration
                                                    </label>
                                                </div>
                                                
                                                <div>
                                                    <label>
                                                        <input type="checkbox" id="use_true_shield" name="use_true_shield" value="1"<?php echo $general_setting[ 'use_true_shield' ] == '1' ? ' checked' : ''; ?> /> Use TrueShield for prevention
                                                    </label>
                                                </div>
                                                
                                            </div>
                                            
                                            <hr />
                                            
                                            <label>
                                                <input type="radio" id="bypass" name="bypass" value="1"<?php echo $general_setting[ 'bypass' ] == '1' ? ' checked' : ''; ?> /> Temporarily Bypass SiteLock's network
                                            </label>
                                            
                                            <?php echo get_submit_button( 'Save Traffic Routing' ); ?>
                                            
                                        </div>
                                    </form>
                                    
                                </div>
                            </div>
                            
                        </div>
                    </div>
            
            
                    
                    <div class="row">
                        <div class="span3">
                            <div class="postbox metabox-holder">
                                <h3><span>Original DNS</span></h3>
                                <div class="inside sl-min-height-150">
                                    <table class="table table-bordered table-fluid">
                                        <tr>
                                            <td class="active">A Records</td>
                                            <td>
                                                <?php foreach ( $dns_orig_a as $ip ) : ?>
                                                    <code><?php echo esc_attr( trim( $ip ) ); ?></code> 
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="active">CNAME</td>
                                            <td><?php echo esc_attr( $dns_orig_cname ); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="span3">
                            <div class="postbox metabox-holder">
                                <h3><span>DNS Settings for SiteLock</span></h3>
                                <div class="inside sl-min-height-150">
                                    <table class="table table-bordered table-fluid">
                                        <tr>
                                            <td class="active">A Records</td>
                                            <td>
                                                <?php foreach ( $dns_new_a as $ip ) : ?>
                                                    <code><?php echo esc_attr( trim( $ip ) ); ?></code> 
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="active">CNAME</td>
                                            <td><?php echo esc_attr( $dns_new_cname ); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="span3">
                            <div class="postbox metabox-holder">
                                <h3><span>SSL Configuration Status</span></h3>
                                <div class="inside sl-min-height-150">
                            
                                    <p>SiteLock SSL detection status: <strong><?php echo $ssl_detected ? 'Detected' : 'Not Detected'; ?></strong></p>
                                            
                                    <span id="ssl_message"></span>
                                    
                                </div>
                            </div>
                        </div>
                        
                        <div class="span3">
                            <div class="postbox metabox-holder">
                                <h3><span>Site IP</span></h3>
                                <div class="inside sl-min-height-150">
                                    
                                    <p>We'll get your source files from this IP address: <code class="site_ip"><?php echo esc_attr( $site_ip ); ?></code></p>
                                    <p style="line-height:16px;"><small><em>
                                        Please allow up to 10 minutes for IP updates to take effect.<br />
                                        Please confirm that the IP you provide is accurate in order to ensure continued web presence.
                                    </em></small></p>
                                    
                                    <div id="site_ip_status"></div>
                                    
                                    <form method="post">
                                        <div class="sl_change_ip_form hide-on-load">
                                            <div class="form-group">
                                                <input type="text" class="form-control change_ip_value" name="new_ip" value="" placeholder="Example: 255.255.255.25" />
                                            </div>
                                            <div class="form-group">
                                                <?php echo get_submit_button( 'Save New IP', 'primary', 'sl_change_ip_submit', false, array( 'id' => 'sl_change_ip_submit' ) ); ?>
                                                &nbsp;&nbsp;
                                                <?php echo get_submit_button( 'Cancel', 'delete', 'sl_change_ip_cancel', false, array( 'id' => 'sl_change_ip_cancel' ) ); ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <?php echo get_submit_button( 'Change IP', 'secondary', 'sl_change_ip', false, array( 'id' => 'sl_change_ip' ) ); ?>
                                        </div>
                                    </form>
                                    
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    
                    <div class="postbox metabox-holder">
                        <h3><span>Cache Settings</span></h3>
                        <div class="inside">
                            
                            <div class="row">
                                <div class="span3">
                                    
                                    <h4>Clear Cache</h4>
                                    
                                    <form method="post">
                                        <div class="row">
                                            <div class="span12">
                                                <div class="padding-bottom-5">
                                                    <?php wp_dropdown_pages( array( 'class' => 'sl_select_width_100' ) ); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="span8">
                                                <?php echo get_submit_button( 'Clear for this page', 'primary', '', false ); ?>
                                            </div>
                                            <div class="span4">
                                                <?php echo get_submit_button( 'Clear All', 'secondary', 'sl_purge_cache', false, array( 'id' => 'sl_purge_cache', 'class' => 'pull-right' ) ); ?>
                                                <div class="clearfix"></div>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <p><br /></p>
                                    <hr />
                                    
                                    <h4>Cache Mode</h4>
                                        <form method="post">
                                            <select name="cache_mode">
                                                <?php foreach ( $cache_modes as $cache_mode_value => $cache_mode_name ) : ?>
                                                    <option value="<?php echo esc_attr( $cache_mode_value ); ?>"<?php echo ( $cache_mode_value == $cache_mode ? ' selected' : '' ); ?>><?php echo esc_html( $cache_mode_name ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <?php echo get_submit_button( 'Save', 'primary', '', false ); ?>
                                        </form>
                                    <hr />
                                    
                                </div>
                                <div class="span9">
                                    
                                    <h4>Cache Settings</h4>
                                    
                                    <form method="post">
                                    
                                        <?php
                                        
                                            $this->table->headings(
                                                array(
                                                    'Name',
                                                    'Actions'
                                                )
                                            );
                                            
                                            $this->table->data(
                                                array( 
                                                    array(
                                                        'Async Validation',
                                                        '<label><input type="checkbox" name="async_validation" id="async_validation" value="1" ' . $async_validation . ' /></label>'
                                                    ),
                                                    array(
                                                        'Content Minification',
                                                        '<label><input type="checkbox" name="minify_javascript" id="minify_javascript" value="1" ' . $minify_javascript . ' /></label> Minify JavaScript<br />
                                                        <label><input type="checkbox" name="minify_css" id="minify_css" value="1" ' . $minify_css . ' /></label> Minify CSS<br />
                                                        <label><input type="checkbox" name="minify_static_html" id="minify_static_html" value="1" ' . $minify_static_html . ' /> Minify static HTML</label>'
                                                    ),
                                                    array(
                                                        'Image Compression',
                                                        '<label><input type="checkbox" name="compress_jpeg" id="compress_jpeg" value="1" ' . $compress_jpeg . ' /> Compress JPEG</label><br />
                                                        <div class="sitelock_jpg_compress_options">
                                                            <label><input type="checkbox" name="aggressive_compression" id="aggressive_compression" value="1" ' . $aggressive_compression . ' /> Aggressive Compression</label><br />
                                                            <label><input type="checkbox" name="progressive_image_rendering" id="progressive_image_rendering" value="1" ' . $progressive_image_rendering . ' /> Progressive Image Rendering</label>
                                                        </div>
                                                        <label><input type="checkbox" name="compress_png" id="compress_png" value="1" ' . $compress_png . ' /> Compress PNG</label>
                                                        '
                                                    ),
                                                    array(
                                                        '"On the fly" Compression',
                                                        '<label><input type="checkbox" name="on_the_fly_compression" id="on_the_fly_compression" value="1" ' . $on_the_fly_compression . ' /></label>'
                                                    ),
                                                    array(
                                                        'TCP Pre-Pooling',
                                                        '<label><input type="checkbox" name="tcp_pre_pooling" id="tcp_pre_pooling" value="1" ' . $tcp_pre_pooling . ' /></label>'
                                                    ),
                                                    array(
                                                        'Comply with no-cache and max-age directives in client requests',
                                                        '<label><input type="checkbox" name="comply_no_cache" id="comply_no_cache" value="1" ' . $comply_no_cache . ' /></label>'
                                                    ),
                                                    array(
                                                        'Comply with Vary: User-Agent',
                                                        '<label><input type="checkbox" name="comply_vary" id="comply_vary" value="1" ' . $comply_vary . ' /></label>'
                                                    ),
                                                    array(
                                                        'Use shortest caching duration in case of conflicts',
                                                        '<label><input type="checkbox" name="use_shortest_caching" id="use_shortest_caching" value="1" ' . $use_shortest_caching . ' /></label>'
                                                    ),
                                                    array(
                                                        'Prefer "last modified" over eTag',
                                                        '<label><input type="checkbox" name="prefer_last_modified" id="prefer_last_modified" value="1" ' . $prefer_last_modified . ' /></label>'
                                                    ),
                                                    array(
                                                        'Apply acceleration setting also to HTTPS',
                                                        '<label><input type="checkbox" name="accelerate_https" id="accelerate_https" value="1" ' . $accelerate_https . ' /></label>'
                                                    ),
                                                    array(
                                                        'Disable client side caching',
                                                        '<label><input type="checkbox" name="disable_client_side_caching" id="disable_client_side_caching" value="1" ' . $disable_client_side_caching . ' /></label>'
                                                    )
                                                )
                                            );

                                            $table_rules = array(
                                                'table' => array( 'class' => array(), 'cellspacing' => array() ),
                                                'th' => array( 'id' => array(), 'class' => array(), 'scope' => array() ),
                                                'tr' => array( 'class' => array() ),
                                                'td' => array( 'class' => array() ),
                                                'thead' => array(),
                                                'tfoot' => array(),
                                                'label' => array(),
                                                'input' => array( 'type' => array(), 'name' => array(), 'id' => array(), 'value' => array(), 'checked' => array() ),
                                                'div' => array( 'class' => array() )
                                            );
                                            
                                            echo wp_kses( $this->table->build_table( false ), $table_rules );
                                            
                                            echo get_submit_button( 'Save Cache Settings', 'primary', 'save_cache_settings' );
                                            
                                        ?>
                                    
                                    </form>
                                    
                                </div>
                            </div>
                            
                            
                        </div>
                    </div>
                    
                    
                        
                    
                </div><!-- /.span12 -->
            </div><!-- /.row -->
        </div><!-- /.wpslp_container -->
    </div><!-- /.sitelock -->
</div><!-- /ie check -->
</div><!-- /.wrap -->

