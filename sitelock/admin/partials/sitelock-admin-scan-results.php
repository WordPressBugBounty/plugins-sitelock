<?php 

    $scan_results_nav = '';
    $i                = 0;
    $active_boxes     = array();
    $first_active     = 1;
    $today            = strtotime( date( 'Y-m-d' ) );
    $groups           = count( $this->groups );

    foreach ( $this->groups as $group_id => $group_data )
    {
        ++$i;

        $active = ( $i == $first_active ? true : false );

        $settings = $group_id == 1 ? '<a href="' . admin_url( 'tools.php?page=' . $this->plugin_name . '&waf_config=true' ) . '" style="float: right; margin-right: 15px;"><small>Settings</small></a>' : '';
        $details_header = '<div class="postbox metabox-holder"><h3>' . $settings . '<span>' . $group_data[ 'sub_name' ] . '</span><div class="clearfix"></div></h3><div class="inside">';
        $details_footer = '</div></div>';

        $scan_results_nav .= '<a href="#" data-id="' . $group_id . '" class="expand-details nav-tab' . ( $active ? ' nav-tab-active' : '' ) . '">';
        $scan_results_nav .= '      <div style="float: left; width: 25px; height: 15px; margin: 2px 0 0 0px;">';
        $scan_results_nav .= sitelock_decorate_scan_results( $group_data[ 'attention_flag' ], true );
        $scan_results_nav .= '      </div>';
        $scan_results_nav .= $group_data[ 'name' ];
        $scan_results_nav .= '<div class="clearfix"></div>';
        $scan_results_nav .= '</a>';

        $html = '';
        $c = 0;

        foreach ( $group_data[ 'boxes' ] as $feature_slug => $feature_data )
        {
            if ( $feature_slug == 'waf' )
            {
                $name = 'TrueShield Traffic';
                $feature_data[ 'name' ] = 'TrueShield';
                $settings = true;
            }
            else
            {
                $name = isset( $feature_data[ 'name' ] ) ? $feature_data[ 'name' ] : ucfirst( $feature_slug );
            }

            // build active display
            ob_start();

                if ( $feature_slug == 'waf' && !empty( $this->waf[ 'data' ] ) )
                {
                    echo '<div class="span12">';

                    if ( $this->waf[ 'status' ] == 'pending' )
                    {
                        ?>
                            <div id="message" class="warning notice notice-warning below-h2">
                                <p>
                                    <?php echo esc_html( $this->waf[ 'message' ] ); ?> <em><?php echo esc_html( $this->waf[ 'details' ] ); ?></em> <a href="<?php echo esc_url_raw( $admin_url ); ?>&waf_setup=true" target="_blank">Click here to resolve</a>
                                </p>
                            </div>
                        <?php
                    }
                    else
                    {
                        ?>
                            <div class="padding-left-15 padding-right-15 waf_stats">
                                <br />
                    
                                <div class="postbox metabox-holder">
                                    <h3><span>Human and Bot Visitor Statistics</span></h3>
                                    <div class="inside">
                                            <div id="sitelock-graph"></div>
                                    </div>
                                </div>
                                <br />
                    
                                <div class="row">
                                    <div class="span3">
                                        <div class="postbox metabox-holder">
                                            <h3><span>Visitors by Country</span></h3>
                                            <div class="inside">
                                                <div id="sitelock-graph-country"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="span3">
                                        <div class="postbox metabox-holder">
                                            <h3><span>Visitors by Client</span></h3>
                                            <div class="inside">
                                                <div id="sitelock-graph-other"></div>
                                            </div>
                                        </div>
                                    </div>
                                
                                    <div class="span3">
                                        <div class="postbox metabox-holder">
                                            <h3><span>Cached Data</span></h3>
                                            <div class="inside">
                                                <div id="sitelock-graph-cache-data"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="span3">
                                        <div class="postbox metabox-holder">
                                            <h3><span>Cached Requests</span></h3>
                                            <div class="inside">
                                                <div id="sitelock-graph-cache-requests"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php

                        foreach ( $this->waf[ 'data' ] as $input ) 
                        {
                            echo esc_html( $input ) . "\n";
                        }
                    }

                    echo '</div>';
                }
                else
                {
                    ++$c;

                    foreach ( $feature_data as $col => $val )
                    {
                        $$col = $val;
                    }

                    $rescan = false;

                    // last run
                    $last_run = !empty( $scanned ) ? $scanned : '';

                    if ( !empty( $scan_code ) && $configured ) 
                    {
                        $rescan = true;

                        // scan url
                        $scan_url = '';

                        // Next scheduled
                        if ( $auto_run > 0 )
                        {
                            $ymd_auto_run = strtotime( date( 'Y-m-d', $auto_run ) );

                            $next_scheduled = $ymd_auto_run >= $today ? 'Today' : date( 'F jS', $auto_run );
                        }
                        else if ( $auto_run == 0 )
                        {
                            $next_scheduled = 'No scans scheduled';
                        }

                        // Next available
                        if ( $manual_run > 0 )
                        {
                            $ymd_manual_run = strtotime( date( 'Y-m-d', $manual_run ) );

                            if ( $ymd_manual_run >= $today )
                            {
                                $next_available = 'Today';

                                // scan url
                                $scan_url = admin_url( 'tools.php?page=sitelock&scan=' . $scan_code );
                            }
                            else
                            {
                                $next_available = date( 'F jS', $manual_run );
                            }
                        }
                        else if ( $manual_run == 0 )
                        {
                            $next_available = 'No remaining manual scans are available.';
                        }
                    }

                    ?>
                    <div class="row">
                        <div class="span4">
                            
                            <h1 class="margin-bottom-0 padding-bottom-0">
                                <?php if ( $rescan ) : ?>

                                    <?php if ( $scan_url != '' ) : ?>

                                        <a href="<?php echo esc_url_raw( $scan_url ); ?>" data-type="<?php echo esc_attr( $scan ); ?>" class="sitelock-security-scan-now sitelock-tooltip"><span>Scan Now</span><i data-type="1" class="dashicons dashicons-update margin-top-5"></i></a>

                                    <?php else : ?>
                                        <a href="#" class="sitelock-tooltip"><span><?php echo esc_html( $next_available[0].$next_available[1] == 'No' ? $next_available : 'Next available scan on ' . $next_available ); ?></span><i class="dashicons dashicons-update margin-top-5"></i></a>
                                    <?php endif; ?>

                                <?php endif; ?>
                                <a target="_blank" href="<?php echo esc_url_raw( sitelock_sso( $slug ) ); ?>">
                                    <?php echo esc_attr( trim( $name ) != '' ? $name : $feature_data[ 'name' ] ); ?>
                                </a>
                            </h1>
                            <p class="margin-top-0 padding-top-0"><?php echo $description; ?></p>
                        </div>
                        <div class="span3">
                            <div style="float: left; width: 25px; padding: 12px 0 0 0;">
                                <?php 
                                    $rules = array(
                                        'a' => array( 'href' => array(), 'target' => array() ),
                                        'div' => array( 'style' => array(), 'class' => array() ),
                                    );
                                    echo wp_kses( sitelock_decorate_scan_results( $icon, true ), $rules ); ?>
                            </div>
                            <div style="float: left;">
                                <p>
                                <?php
                                    switch ( $color )
                                    {
                                        case 'yellow':
                                            $message = 'Needs Review';
                                            break;

                                        case 'green':
                                            $message = 'No issue found';
                                            break;

                                        case 'red':
                                            $message = 'Issue found';
                                            break;
                                    }

                                    echo esc_attr( $message );
                                ?>
                                </p>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="span4">
                            <?php
                                if ( !empty( $scan_code ) || $last_run != '' )
                                {
                                    ?>
                                    <p><strong>Scan Status</strong></p>
                                    <p>
                                        <?php

                                            $scan_status = false;

                                            if ( $last_run != '' )
                                            {
                                                ?>
                                                    <strong>Last run:</strong> <?php echo esc_attr( $last_run ); ?>
                                                    <br />
                                                <?php

                                                $scan_status = true;
                                            } 
                                            
                                            if ( !empty( $scan_code ) && $configured ) 
                                            {
                                                ?>
                                                    <strong>Next scheduled:</strong> <?php echo esc_attr( $next_scheduled ); ?>
                                                    <br />
                                                    
                                                    <strong>Next available:</strong> <?php echo esc_attr( $next_available ); ?>
                                                <?php

                                                $scan_status = true;
                                            }

                                            if ( !$scan_status )
                                            {
                                                ?>
                                                <em>Not available at the moment.</em>
                                                <?php
                                            }
                                        ?>
                                    </p>
                                    <?php
                                }
                            ?>
                        </div>
                    </div>
                    <hr />
                    <?php
                }

                $html .= ob_get_contents();
            ob_end_clean();
        }

        $html = '<div class="row">' . $html . '<div class="clearfix clear"></div></div>';
        
        $active_boxes[ $group_id ] = array( 
            'active' => $active, 
            'name' => $group_data[ 'sub_name' ], 
            'html' => $details_header . $html . $details_footer 
        );
    }

