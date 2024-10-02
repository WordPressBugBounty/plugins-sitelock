<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
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

                    <div class="wpslp_summary_box <?php echo esc_html( $this->site_parent_data[ 'attention_flag' ] ); ?>">
                        <!-- div class="status"></div -->
                        <div class="row">
                            <div class="span10">

                                <form method="post" class="hide-on-load">
                                    <input type="submit" name="refresh_scan_results" id="refresh_scan_results" value="Refresh Scan Results" />
                                </form>

                                <h2><a data-type="1" id="refresh_results" class="dashicons dashicons-update sitelock-tooltip"><span>Refresh results</span></a> SiteLock Security</h2>

                            </div>
                            <div class="span2 text-right">
                                <br />
                                <small style="font-size: 12px;"><a href="https://wpdistrict.sitelock.com/sitelock-plugin-faq/" target="_blank">Tell Me More</a> | <a href="<?php echo $admin_url; ?>&logout=true">Disconnect</a></small>
                            </div>
                        </div>
                    
                    </div>
                
                    <?php

                        if ( isset( $_POST[ 'refresh_scan_results' ] ) )
                        {
                            ?>
                            <div class="clearfix"></div>
                                <div id="message" class="success notice notice-success is-dismissible below-h2">
                                    <p>
                                        Scan results have been refreshed.
                                    </p>
                                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                                </div>
                            <?php
                        }

                        if ( $this->banner != '' )
                        {
                            ?>
                            <div class="clearfix"></div>
                                <div id="message" class="error notice notice-error is-dismissible below-h2">
                                    <p>
                                        <?php echo esc_html( $this->banner ); ?>
                                    </p>
                                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                                </div>
                            <?php
                        }

                        if ( !empty( $this->alert ) )
                        {
                            $alerts = count( $this->alert );

                            ?>
                            <div class="clearfix"></div>
                                <div id="message" class="error notice notice-error below-h2">
                                    <p>
                                        There <?php echo $alerts == 1 ? 'is' : 'are'; ?> <?php echo esc_attr( $alerts ); ?> security item<?php echo $alerts == 1 ? '' : 's'; ?> that need<?php echo $alerts == 1 ? 's' : ''; ?> your attention.
                                    </p>
                                    <!-- button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button-->

                                    <hr />

                                    <ul>
                                    <?php foreach ( $this->alert as $slug => $data ) : ?>
                                        <li><a href="<?php echo esc_url_raw( sitelock_sso( $slug ) ); ?>" target="_blank"><strong><?php echo isset($data[ 'display' ]) ? esc_html( $data[ 'display' ] ) : ''; ?></strong></a> <?php echo !empty( $data[ 'sync_message' ] ) ? esc_html( $data[ 'sync_message' ] ) : ''; ?></li>
                                    <?php endforeach; ?>
                                    </ul>

                                    <a href="<?php echo esc_url_raw( sitelock_sso() ); ?>" target="_blank" class="view_alert_details hide-on-load"></a>

                                    <p>
                                    <?php submit_button( 'View Details', 'primary', 'view_alert_details', false ); ?>
                                    <?php submit_button( 'Dismiss', 'secondary', 'dismiss_alert', false ); ?>
                                    </p>
                                </div>
                            <?php
                        }

                        // config wizard
                        if ( !empty( $this->config ) )
                        {
                            $configs = count( $this->config );

                            ?>
                            <div class="clearfix"></div>
                                <div id="message" class="warning notice notice-warning below-h2">
                                    <p>
                                        There <?php echo $configs == 1 ? 'is' : 'are'; ?> <?php echo esc_attr( $configs ); ?> configuration item<?php echo $configs == 1 ? '' : 's'; ?> that need<?php echo $configs == 1 ? 's' : ''; ?> your attention.
                                    </p>
                                    <!-- button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button -->

                                    <hr />

                                    <ul>
                                    <?php foreach ( $this->config as $slug => $data ) : ?>
                                        <li>
                                            <p>
                                                <a href="<?php echo esc_url_raw( sitelock_sso( $data[ 'url' ] ) ); ?>" target="_blank"><strong><?php echo isset($data[ 'display' ]) ? esc_html( $data[ 'display' ] ) : ''; ?></strong></a>
                                                <?php echo !empty( $data[ 'sync_message' ] ) ? ' ' . esc_html( $data[ 'sync_message' ] ) : ''; ?>
                                            </p>
                                        </li>
                                    <?php endforeach; ?>
                                    </ul>

                                    <a href="<?php echo esc_url_raw( sitelock_sso() ); ?>" target="_blank" class="view_config_details hide-on-load"></a>

                                    <p>
                                    <?php submit_button( 'View Details', 'primary', 'view_config_details', false ); ?>
                                    <?php submit_button( 'Dismiss', 'secondary', 'dismiss_config', false ); ?>
                                    </p>
                                </div>
                            <?php
                        }
                    ?>

                    <?php if ( !empty( $this->config ) || !empty( $this->alert ) ) : ?>
                        <div class="sl-details sl-blur-it">
                            <div class="sl-stop-actions"></div>
                    <?php else : ?>
                        <div class="sl-details">
                    <?php endif; ?>

                        <br />

                        <?php include( 'sitelock-admin-scan-results.php' ); ?>
                        
                        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                            <?php
                                $rules = array(
                                    'a' => array( 'href' => array(), 'data-id' => array(), 'class' => array() ),
                                    'div' => array( 'style' => array(), 'class' => array() ),
                                );
                                echo wp_kses( $scan_results_nav, $rules );
                            ?>
                        </nav>

                        <div class="big_active_box">
                            <?php
                                foreach ( $active_boxes as $group_id => $data )
                                {
                                    ?>
                                    <div id="group_<?php echo esc_attr( $group_id ); ?>" class="expanded-details <?php echo ( $data[ 'active' ] ? 'now-active' : '' ); ?>">
                                        <?php
                                            $rules = array(
                                                'a' => array( 'href' => array(), 'target' => array(), 'class' => array(), 'style' => array(), 'data-type' => array() ),
                                                'i' => array( 'class' => array(), 'data-type' => array() ),
                                                'em' => array(),
                                                'div' => array( 'style' => array(), 'class' => array() ),
                                                'span' => array(),
                                                'h1' => array( 'class' => array() ),
                                                'h3' => array(),
                                                'p' => array( 'class' => array() ),
                                                'hr' => array(),
                                                'br' => array(),
                                                'strong' => array(),
                                                'small' => array(),
                                            );
                                            echo wp_kses( $data[ 'html' ], $rules );
                                        ?>
                                    </div>
                                    <?php
                                }
                            ?>
                        </div><!-- /.big_active_box -->

      
                    </div><!-- /.sl-details -->

                    <!--
                        </div>
                    </div>
                    -->
                    
                </div><!-- .span12 -->
            </div><!-- .row -->
    
        </div><!-- .wpslp_container -->

        <input type="hidden" id="if_has_cache_data"     value="<?php echo esc_attr( !empty( $this->cache_data ) ? 'true' : '' ); ?>" />
        <input type="hidden" id="cache_data_total"      value="<?php echo esc_attr( sitelock_bytes_to_mb( $this->total_bytes ) ); ?>" />
        <input type="hidden" id="cache_data_saved"      value="<?php echo esc_attr( sitelock_bytes_to_mb( $this->saved_bytes ) ); ?>" />
        
        
        <input type="hidden" id="if_has_cache_requests" value="<?php echo esc_attr( !empty( $this->cache_requests ) ? 'true' : '' ); ?>" />
        <input type="hidden" id="cache_requests_total"  value="<?php echo esc_attr( $this->total_requests ); ?>" />
        <input type="hidden" id="cache_requests_saved"  value="<?php echo esc_attr( $this->saved_requests ); ?>" />

        
        <input type="hidden" id="if_has_chart_data"     value="<?php echo esc_attr( !empty( $this->chart_data ) ? 'true' : '' ); ?>" />
        <input type="hidden" id="if_has_graph_country"  value="<?php echo esc_attr( !empty( $this->chart_data_country ) ? 'true' : '' ); ?>" />
        <input type="hidden" id="if_has_graph_other"    value="<?php echo esc_attr( !empty( $this->chart_data_other   ) ? 'true' : '' ); ?>" />

    </div><!-- .sitelock -->

</div><!-- /IE -->
</div><!-- /.wrap -->


