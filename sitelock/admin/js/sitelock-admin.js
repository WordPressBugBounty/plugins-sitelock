(function( $ ) {
	'use strict';

        var blue        = "#348fe2";
        var blueLight   = "#5da5e8";
        var blueDark    = "#1993E4";
        var aqua        = '#777';
        var aquaLight   = "#6dc5de";
        var aquaDark    = "#3a92ab";
        var green       = "#00acac";
        var greenLight  = "#33bdbd";
        var greenDark   = "#008a8a";
        var orange      = "#f59c1a";
        var orangeLight = "#f7b048";
        var orangeDark  = "#c47d15";
        var dark        = "#2d353c";
        var grey        = "#b6c2c9";
        var purple      = "#727cb6";
        var purpleLight = "#8e96c5";
        var purpleDark  = "#5b6392";
        var red         = "#ff5b57";
        
        if ( $( '.sitelock #if_has_cache_data' ).val() == 'true' ) {
            var total_bytes = $( '#cache_data_total' ).val();
            var saved_bytes = $( '#cache_data_saved' ).val();
            
            Morris.Bar({
                element: 'sitelock-graph-cache-data',
                data: [
                    { y: 'Megabytes', a: total_bytes, b: saved_bytes }
                ],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Served', 'Served from Cache'],
                parseTime: false,
                barColors: [ blueLight, orange ],
                // hideHover: true,
                resize: true
            });
        }
        
        if ( $( '.sitelock #if_has_cache_requests' ).val() == 'true' ) {
            var total_requests = $( '#cache_requests_total' ).val();
            var saved_requests = $( '#cache_requests_saved' ).val();
            
            Morris.Bar({
                element: 'sitelock-graph-cache-requests',
                data: [
                    { y: 'Requests', a: total_requests, b: saved_requests }
                ],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Served', 'Served from Cache'],
                parseTime: false,
                barColors: [ blueLight, orange],
                // hideHover: true,
                resize: true
            });
        }
        
        if ( $( '.sitelock #if_has_graph_country' ).val() == 'true' ) {
            // build chart_country
            
            var chart_data = [];
            var data       = '';
            var obj        = '';
            var label      = '';
            var value      = 0;
            
            $( '.country_visits' ).each( function() {
                
                label = $( this ).attr( 'data-label' );
                
                if ( label != '' )
                {
                    value = parseFloat( $( this ).attr( 'data-value' ) != '' ? $( this ).attr( 'data-value' ) : 0 );
                    
                    // build data as string
                    data = '{ "label": "' + label + '", "value": ' + value + ' }';
                    
                    // convert string to json object
                    obj = $.parseJSON( data );
                    
                    // add json object to chart_data
                    chart_data.push( obj );
                }
            });
            
            Morris.Donut({
                element: 'sitelock-graph-country',
                data: chart_data,
                labelColor: dark,
                resize: true,
                colors: [
                    blueLight,
                    orange,
                    dark,
                    aqua
                ]
            });
        }
        
        
        
//--------- Bot Visits
        if ( $( '.sitelock #if_has_graph_other' ).val() == 'true' ) {
            var chart_data = [];
            var data       = '';
            var obj        = '';
            var label      = '';
            var value      = 0;
            
            $( '.bot_visits' ).each( function() {
                
                label = $( this ).attr( 'data-label' );
                
                if ( label != '' )
                {
                    value = parseFloat( $( this ).attr( 'data-value' ) != '' ? $( this ).attr( 'data-value' ) : 0 );
                    
                    // build data as string
                    data = '{ "label": "' + label + '", "value": ' + value + ' }';
                    
                    // convert string to json object
                    obj  = $.parseJSON( data );
                    
                    // add json object to chart_data
                    chart_data.push( obj );
                }
            });
            
            Morris.Donut({
                element:    'sitelock-graph-other',
                data:       chart_data,
                labelColor: dark,
                resize:     true,
                colors: [
                    blueLight,
                    orange,
                    dark,
                    aqua
                ]
            });
        }
        
        
//--------- Human Bot Stats
        if ( $( '.sitelock #if_has_chart_data' ).val() == 'true' ) {
            console.log( 'we have chart data' );

            var chart_data = [];
            var data       = '';
            var obj        = '';
            
            $( '.chart_data' ).each( function() {
                
                // build data as string
                data = '{ "y": "' + $( this ).attr( 'data-y' ) + '", "a": ' + $( this ).attr( 'data-a' ) + ', "b": ' + $( this ).attr( 'data-b' ) + ' }';
                
                // convert string to json object
                obj  = $.parseJSON( data );
                
                // add json object to chart_data
                chart_data.push( obj );
            });
            
            Morris.Line({
                element:         'sitelock-graph',
                data:            chart_data,
                xkey:            'y',
                ykeys:           [ 'a', 'b' ],
                labels:          [ 'Human Visitors', 'Bot Visitors' ],
                parseTime:       false,
                lineColors:      [ blueLight, orange ],
                pointFillColors: [ dark, aqua ],
                resize:          true
            });
        } else {
            console.log( 'no chart chart data, why?' );
        }
			
        $( '#sl_change_ip' ).click( function() {
                $( this ).hide();
                $( '.sl_change_ip_form' ).slideDown( 250 );
                return false;
        });
        
        $( '#sl_change_ip_cancel' ).click( function() {
                $( '.sl_change_ip_form' ).slideUp( 250 );
                setTimeout( function() {
                $( '#sl_change_ip' ).show();	
                }, 250 );
                return false;
        });

        $( '.sitelock #refresh_results' ).click( function() {
            $( '#refresh_scan_results' ).click();
            return false;
        });

        // expand scan result details
        $( '.sitelock .expand-details' ).click( function( e ) {

            // prevent the default
            e.preventDefault();

            // remove current details
            $( '.expanded-details.now-active' ).removeClass( 'now-active' );

            // remove current selected tab
            $( '.nav-tab-active' ).removeClass( 'nav-tab-active' );

            // set new selected tab
            $( this ).addClass( 'nav-tab-active' );

            // get id of new details
            var group_id = $( '.nav-tab-active' ).data( 'id' );

            // show new details
            $( '#group_' + group_id ).addClass( 'now-active' );

            return false;
        });
        
        $( '.sitelock #bypass' ).change( function() {
                var bypass_status = $( '#bypass:checked' ).val();
                
                if ( bypass_status == '0' ) {
                        $( '.sitelock_true_options' ).slideDown( 250 );
                } else {
                        $( '.sitelock_true_options' ).slideUp( 250 );
                }
        });
        
        $( '.page_protect' ).click( function() {
           var response = sl_verify_page_protect();
           
           if ( response )
           {
                   return true;
           }
           
           return false;
        });
            
        function sl_verify_page_protect()
        {
           if ( confirm( 'Page Protect requires users to be setup in the SiteLock dashboard under Settings > TrueShield > Authentication.  Have you set up your users yet?' ) )
           {
                   return true;
           }
           
           return false;
        }

        $( '#view_config_details, #view_alert_details' ).click( function( e ) {
            
            // stop processing
            e.preventDefault();

            // get id and convert to class
            var this_class = '.' + $( this ).attr( 'id' );

            // the url
            var url = $( this_class ).attr( 'href' );

            // open url
            window.open( url, '_blank' );
        });

        $( '#dismiss_config, #dismiss_alert' ).click( function( e ) {

            // stop processing
            e.preventDefault();

            // hide
            $( this ).parent( 'p' ).parent( 'div' ).remove();

            var i = 0;

            if ( $( '#dismiss_config' ).attr( 'class' ) != undefined )
            {
                console.log( 'still have a config' );
                ++i;
            }

            if ( $( '#dismiss_alert' ).attr( 'class' ) != undefined )
            {
                console.log( 'still have an alert' );
                ++i;
            }
            
            if ( i == 0 )
            {
                // show sl details
                $( '.sl-details' ).removeClass( 'sl-blur-it' );
                $( '.sl-stop-actions' ).remove();
            }

            return false;
        })
            
            $( '.sitelock_nav li a' ).click( function( e ) {
                
                e.preventDefault();
                var div = $( this ).attr( 'href' );
                $( 'a.current' ).removeClass( 'current' );
                $( this ).addClass( 'current' );
                $( '.tab-box' ).hide();
                $( div ).show();
                return false;
            
            });
            
            var collapse_sites = false;

            $( '.sitelock_my_sites' ).click( function() {
                
                $( 'ul.wpslp_site_list' ).slideToggle( 250 );
                $( 'ul.wpslp_site_list' ).toggleClass( 'showing' );
                $( this ).children( 'span.dashicons' ).toggleClass( 'dashicons-minus' ).toggleClass( 'dashicons-plug' );

                if ( $( 'ul.wpslp_site_list' ).hasClass( 'showing' ) )
                {
                    collapse_sites = true;
                }
                else
                {
                    collapse_sites = false;
                }

                return false;
                
            });

            $(document).mouseup(function (e)
            {
                var container = $( "ul.wpslp_site_list" );

                if (!container.is(e.target) // if the target of the click isn't the container...
                    && container.has(e.target).length === 0
                    && collapse_sites === true ) // ... nor a descendant of the container
                {
                    container.hide();
                    collapse_sites = false;
                }
            });
            
            $( '.sitelock .circle a' ).click( function() {
                
                var the_url = $( this ).attr( 'href' );
                
                window.open( the_url, '_self' );
                return false;
                
            });
            
            $( '.sitelock .circle' ).click( function() {
                
                var the_url = $( this ).attr( 'data-url' );
                var location = $( this ).attr( 'data-location' );
                
                window.open( the_url, location );
                return false;
            });
            
            $( '.sitelock .expand_features' ).click( function( e ) {
                
                e.preventDefault();
                $( this ).toggleClass( 'active' );
                $( this ).parent( 'div' ).parent( 'div.row' ).parent( 'li' ).children( 'ul' ).slideToggle( 250 );
                
                return false;
            
            });
            
/*****************************************************************
 * Circle Action
*****************************************************************/    
            $( ".sitelock .circle" ).mouseenter( function() {
                
                // $( this ).children( ".circle-inner" ).children( ".icon-status" ).slideUp( 250 );
                // $( this ).children( ".circle-inner" ).children( ".text-status" ).slideDown( 250 );
                // $( this ).children( ".circle-inner" ).children( ".scan-stats" ).slideDown( 250 );
                // $( this ).children( ".circle-inner" ).children( ".action-items" ).show();
                
                if ( $( this ).children( ".circle-inner" ).children( ".resolve" ).length ) {
                    $( this ).children( ".circle-inner" ).children( ".resolve" ).show();
                }
                
            });
            
            $( ".sitelock .circle" ).mouseleave( function() {
                
                // $( this ).children( ".circle-inner" ).children( ".icon-status" ).slideDown( 250 );
                // $( this ).children( ".circle-inner" ).children( ".text-status" ).slideUp( 250 );
                // $( this ).children( ".circle-inner" ).children( ".scan-stats" ).slideUp( 250 );
                // $( this ).children( ".circle-inner" ).children( ".action-items" ).hide();
                
                if ( $( this ).children( ".circle-inner" ).children( ".resolve" ).length ) {
                    $( this ).children( ".circle-inner" ).children( ".resolve" ).hide();
                }
                
            });
			
			
			$( '.view_step' ).click( function() {
				var step = $( this ).data( 'step' );
				
				$( '.step_box' ).hide();
				$( '#sl_step' + step ).show();
				
				return false;
			});

        // });


})( jQuery );

