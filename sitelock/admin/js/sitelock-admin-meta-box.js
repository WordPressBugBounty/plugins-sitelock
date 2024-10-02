(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-specific JavaScript source
	 * should reside in this file.
	 *
	 * Note that this assume you're going to use jQuery, so it prepares
	 * the $ function reference to be used within the scope of this
	 * function.
	 *
	 * From here, you're able to define handlers for when the DOM is
	 * ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * Or when the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 */

        $( '.sitelock_set_scan_page_type' ).click( function() {
            var type = $( this ).data( 'type' );
            $( '#sitelock_scan_page_type' ).val( type );
            return false;
        });
		
        $( '.page_protect_option' ).change( function() {
           if ( $( this ).val() == 'on' )
           {
                var response = sl_verify_page_protect();
                
                if ( response )
                {
                        return true;
                }
                else
                {
                        $( this ).val( 'off' );
                }
           }
        });
            
        function sl_verify_page_protect()
        {
           if ( confirm( 'Page Protect requires users to be setup in the SiteLock dashboard under Settings > TrueShield > Authentication.  Have you set up your users yet?' ) )
           {
                   return true;
           }
           
           return false;
        }


})( jQuery );
