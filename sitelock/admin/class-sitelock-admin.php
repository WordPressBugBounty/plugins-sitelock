<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin
 * @author     Todd Low <tlow@sitelock.com>
 */

class Sitelock_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.9.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name; 
    

    /**
     * The version of this plugin.
     *
     * @since    1.9.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    

    /**
     * The api class
     * 
     * @since    1.9.0
     * @access   public
     */
    public $api;
    
    
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.9.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) 
    {
        /**
         * Sets the plugin_name and version
         *
         * @since   1.9.0
         */
        $this->plugin_name    = $plugin_name;
        $this->version        = $version;
        

        /**
         * If requesting to logout, remove token from wp_options
         * and redirect user to connect
         *
         * @since   1.9.0
         */
        if ( !empty( $_GET[ 'logout' ] ) ) {
            // delete all cache data
            delete_option( 'wpslp_options'              );
            delete_option( 'sitelock_account_sites'     );
            delete_option( 'sitelock_account_scaninfo'  );
            delete_option( 'sitelock_malware_get_scan'  );
            delete_option( 'sitelock_word_quick'        );
            
            header( 'Location: ' . admin_url() . 'tools.php?page=' . $this->plugin_name );
            exit;
        }

        /**
         * Load dependencies and instantiate API class
         *
         * @since   1.9.0
         */
        $this->load_dependencies();

        /**
         * Create tools page
         *
         * @since   1.9.0
         */
        add_action( 'admin_menu', array( $this, 'create_tools_page' ) );

        /**
         * Add dashboard widget
         *
         * @since   1.9.0
         */
        add_action( 'wp_dashboard_setup', array( $this, 'create_dashboard_widget' ) );

        /**
         * Add links to admin bar
         *
         * @since   1.9.0
         */
        add_action( 'admin_bar_menu', array( $this, 'sitelock_add_toolbar_items' ), 100 );

        /**
         * Add meta box to edit page
         *
         * @since   1.9.0
         * http://codex.wordpress.org/Function_Reference/add_meta_box
         */
        add_action( 'add_meta_boxes', array( $this, 'display_sitelock_scan_results' ) );

        /**
         * Save post action
         *
         * @since   2.0.0
         */
        add_action( 'save_post', array( $this, 'save_page_protect' ), 10, 3 );

        /** 
         * Add bulk options to page list
         * 
         * @since   3.5.0
         */
        if ( is_admin() ) 
        {
            // admin actions/filters
            add_action( 'admin_footer-edit.php', array( &$this, 'custom_bulk_admin_footer' ) );
            add_action( 'load-edit.php',         array( &$this, 'custom_bulk_action' ) );
            add_action( 'admin_notices',         array( &$this, 'custom_bulk_admin_notices' ) );
        }
    }
    

    /**
     * Loads dependencies
     *
     * @since    1.9.0
     */
    private function load_dependencies()
    {
        /**
         * Tables class
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sitelock-tables.php';
        
        /**
         * Loads in functions we need
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/functions-sitelock-admin.php';

        /**
         * The class responsible for all methods related to using our external API
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sitelock-api.php';
        
        /**
         * The zip file creation class
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sitelock-zip.php';

        $this->api   = new Sitelock_API( $this->version );
        $this->table = new Sitelock_Table();
        $this->table2= new Sitelock_Table();

        /** 
         * Add thickbox
         */
        // wp_enqueue_script( 'thickbox' );
        // wp_enqueue_style( 'thickbox' );
    }


    /** 
     * SMART scan types
     * 
     * @since   3.5.0
     */
    const SMART_FIND = 'find';
    const SMART_FIX = 'fix';
    const SMART_FIND_IN_CODE = 'find_in_code';


    /**
     * Add the custom Bulk Action to the select menus
     * 
     * @since   3.5.0
     */
    function custom_bulk_admin_footer() 
    {
        global $post_type;
        
        if ( $post_type == 'page' ) 
        {
            // get smart config/fix settings
            $this->get_smart_settings();

            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                        // jQuery('<option>').val('<?php echo esc_attr( self::SMART_FIND ); ?>').text('<?php _e('Scan with SiteLock - Find')?>').appendTo("select[name='action']");

                        <?php if ( $this->can_fix ) : ?>
                        jQuery('<option>').val('<?php echo esc_attr( self::SMART_FIX ); ?>').text('<?php _e('Clean malware in code')?>').appendTo("select[name='action']");
                        <?php else : ?>
                        jQuery('<option>').val('<?php echo esc_attr( self::SMART_FIND_IN_CODE ); ?>').text('<?php _e('Find malware in code')?>').appendTo("select[name='action']");
                        <?php endif; ?>
                        
                        // jQuery('<option>').val('export').text('<?php _e('Export')?>').appendTo("select[name='action2']");
                    });
                </script>
            <?php
        }
    }
    
    
    /**
     * Bulk Action
     * 
     * @since   3.5.0
     */
    function custom_bulk_action() 
    {
        global $typenow;

        $post_type = $typenow;

        if ( $post_type == 'page' ) 
        {   
            // get the action
            $wp_list_table = _get_list_table( 'WP_Posts_List_Table' ); // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
            
            // get action
            $action = $wp_list_table->current_action();
            
            // allowed actions
            $allowed_actions = array( 
                self::SMART_FIND, 
                self::SMART_FIX, 
                self::SMART_FIND_IN_CODE 
            );

            // is this action allowed?
            if ( !in_array( $action, $allowed_actions ) ) 
            {
                return;
            }
            
            // security check
            check_admin_referer( 'bulk-posts' );
            
            // make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
            if ( isset( $_REQUEST[ 'post' ] ) ) 
            {
                $post_ids = array_map( 'intval', $_REQUEST[ 'post' ] );
            }
            
            if ( empty( $post_ids ) ) 
            {
                return;
            }
            
            // this is based on wp-admin/edit.php
            $sendback = remove_query_arg( 
                array( 
                    'exported', 
                    'untrashed', 
                    'deleted', 
                    'ids' 
                ), 
                wp_get_referer() 
            );

            if ( !$sendback )
            {
                $sendback = admin_url( "edit.php?post_type=$post_type" );
            }
            
            $pagenum = $wp_list_table->get_pagenum();

            $sendback = add_query_arg( 
                'paged', 
                $pagenum, 
                $sendback 
            );

            $redirect = false;
            
            switch ( $action ) 
            {
                case self::SMART_FIND:
                    $find = 0;

                    // submit scan
                    $this->api->post_queue_scan( $this->site_id, 'site_scan' );
                    
                    foreach ( $post_ids as $post_id ) 
                    {
                        // $this->smart_find( $post_id );
                        ++$find;
                    }
                    
                    // set sendback
                    $sendback = add_query_arg( 
                        array(
                            'find' => $find, 
                            'ids' => join( ',', $post_ids ) 
                        ), 
                        $sendback 
                    );
                    
                    break;

                case self::SMART_FIX:
                    $fix = 0;

                    // submit scan
                    $this->api->post_queue_scan( $this->site_id, 'smart_scan' );

                    foreach ( $post_ids as $post_id )
                    {
                        // $this->smart_fix( $post_id );
                        ++$fix;
                    }
                    
                    $sendback = add_query_arg( 
                        array(
                            'fix' => $fix, 
                            'ids' => join( ',', $post_ids ) 
                        ), 
                        $sendback 
                    );

                    break;

                case self::SMART_FIND_IN_CODE:
                    $find_in_code = 0;

                    $redirect = admin_url( 'tools.php?page=sitelock&smart_config=true' );

                    update_option( 'sl_smart_post_ids', $post_ids );

                    // foreach ( $post_ids as $post_id )
                    // {
                    //     $this->smart_find_in_code( $post_id );
                    //     ++$find_in_code;
                    // }

                    $sendback = add_query_arg( 
                        array(
                            'find_in_code' => $find_in_code, 
                            'ids' => join( ',', $post_ids ) 
                        ), 
                        $sendback 
                    );

                    break;
                
                default: 
                    return;
            }

            // redirect
            if ( !empty( $redirect ) )
            {
                wp_redirect( $redirect );
                exit;
            }
            
            $sendback = remove_query_arg( 
                array(
                    'action', 
                    'action2', 
                    'tags_input', 
                    'post_author', 
                    'comment_status', 
                    'ping_status', 
                    '_status', 
                    'post',
                    'bulk_edit', 
                    'post_view'
                ), 
                $sendback 
            );
            
            wp_redirect( $sendback );

            exit();
        }
    }
    
    
    /**
     * Display an admin notice on the Posts page after exporting
     * 
     * @since   3.5.0
     */
    function custom_bulk_admin_notices() 
    {
        global $post_type, $pagenow;
        
        if ( $pagenow == 'edit.php' && $post_type == 'page' )
        {
            $total = 0;

            $types = array( 
                self::SMART_FIND, 
                self::SMART_FIX, 
                self::SMART_FIND_IN_CODE
            );

            foreach ( $types as $type )
            {
                if ( isset( $_REQUEST[ $type ] ) && (int) $_REQUEST[ $type ] ) 
                {
                    $total = sanitize_key( $_REQUEST[ $type ] );
                    continue;
                }
            }

            if ( $total > 0 )
            {
                $message = sprintf( 
                    _n( 
                        'Pages scanned.', 
                        '%s pages scanned.', 
                        $total 
                    ), 
                    number_format_i18n( 
                        $total 
                    ) 
                );

                echo wp_kses( "<div class=\"updated\"><p>{$message}</p></div>" );
            }
        }
    }
    

    /** 
     * SMART find bulk option
     * 
     * @since   3.5.0
     */
    private function smart_find( $post_id ) 
    {
        // submit malware scan
        // $this->api->post_queue_scan( $this->site_id, 'site_scan' );

        return true;
    }
    

    /** 
     * SMART fix bulk option
     * 
     * @since   3.5.0
     */
    private function smart_fix( $post_id ) 
    {
        // no action
        return true;
    }
    

    /** 
     * SMART find in code bulk option
     * 
     * @since   3.5.0
     */
    private function smart_find_in_code( $post_id ) 
    {
        // no action
        return true;
    }


    /** 
     * Get smart config/fix settings from db
     * 
     * @since   3.5.0
     */
    private function get_smart_settings()
    {
        // check if we already retrieved settings
        if ( empty( $this->can_config ) )
        {
            $options = get_option( 'sitelock_smart_config_fix' );

            $this->can_config = !empty( $options[ 'can_config' ] );
            $this->can_fix    = !empty( $options[ 'can_fix'    ] );
        }

        return true;
    }
    

    /**
     * Creates sitelock tools page
     *
     * @since    1.9.0
     */
    public function create_tools_page() {
        add_submenu_page(
            'tools.php',
            'SiteLock Security',
            'SiteLock Security',
            'manage_options',
            'sitelock',
            array( $this, 'main_options_page' )
        );
    }
    

    /**
     * Adds dashboard widget
     *
     * @since    1.9.0
     */
    public function create_dashboard_widget() {
        wp_add_dashboard_widget(
            'sitelock_security_overview',            // Widget slug.
            'SiteLock Security Overview',            // Title.
            array( $this, 'sl_dashboard_widget' )    // Display function.
        ); 
    }
    

    /**
     * Adds links to admin bar
     *
     * @since    1.9.0
     */
    public function sitelock_add_toolbar_items( $admin_bar ) {

        $indicator = '';

        if ( isset( $this->api->wpslp_options[ 'auth_key' ] ) && $this->api->wpslp_options[ 'auth_key' ] ) {

            if ( empty( $this->wpslp_data ) )
            {
                $this->site_parent_data();
            }

            $color = isset( $this->site_parent_data[ 'attention_flag' ] ) ? $this->site_parent_data[ 'attention_flag' ] : '';
            
            if ( $color != '' )
            {
                $indicator = '<div style="width: 10px; height: 10px; border-radius: 10px; background-color: ' . $color . '; margin: 0 10px 0 0; display: inline-block;"></div>';
            }
            
            if ( !isset( $this->display_built ) )
            {
                $this->build_display();
            }
        }

        $admin_bar->add_menu( array(
            'id'    => 'sitelock-security',
            'title' => $indicator . 'SiteLock Security',
            'href'  => '#',
        ));
        $admin_bar->add_menu( array(
            'id'     => 'sitelock-security-scan',
            'parent' => 'sitelock-security',
            'title'  => 'Scan Summary',
            'href'   => admin_url( 'tools.php?page=' . $this->plugin_name ),
            'meta'   => array(
                'title'  => __( 'Scan Summary' ),
                'target' => '',
                'class'  => 'sitelock-security-scan'
            ),
        ));
        $admin_bar->add_menu( array(
            'id'     => 'sitelock-security-badge',
            'parent' => 'sitelock-security',
            'title'  => 'Badge Settings',
            'href'   => admin_url( 'tools.php?page=' . $this->plugin_name . '&badge_settings=true' ),
            'meta'   => array(
                'title'  => __( 'Badge Settings' ),
                'target' => '',
                'class'  => 'sitelock-security-badge'
            ),
        ));
        
        if ( !empty( $this->waf[ 'config' ][ 'has_self_serve' ] ) )
        {
            $admin_bar->add_menu( array(
                'id'     => 'sitelock-security-trueshield',
                'parent' => 'sitelock-security',
                'title'  => 'TrueShield Settings',
                'href'   => admin_url( 'tools.php?page=' . $this->plugin_name . '&waf_config=true' ),
                'meta'   => array(
                    'title'  => __( 'TrueShield Configuration' ),
                    'target' => '',
                    'class'  => 'sitelock-security-trueshield'
                ),
            ));
        }
        
        $admin_bar->add_menu( array(
            'id'     => 'sitelock-security-smart',
            'parent' => 'sitelock-security',
            'title'  => 'Source Code Scan',
            'href'   => admin_url( 'tools.php?page=' . $this->plugin_name . '&smart_config=true' ),
            'meta'   => array(
                'title'  => __( 'Source Code Scan' ),
                'target' => '',
                'class'  => 'sitelock-security-smart'
            ),
        ));

        /**
        $billing_status = strtolower( $this->site_parent_data[ 'status' ] );
        $billing_status = $billing_status == 'active' ? '<span class="dashicons dashicons-thumbs-up"></span>' : '<span class="dashicons dashicons-thumbs-up"></span>';
        
        $admin_bar->add_menu( array(
            'id'     => 'sitelock-security-billing-status',
            'parent' => 'sitelock-security',
            'title'  => $billing_status . ' Billing Status',
            'href'   => admin_url( 'tools.php?page=' . $this->plugin_name . '&billing_status=true' ),
            'meta'   => array(
                'title'  => __( 'Billing Status' ),
                'target' => '',
                'class'  => 'sitelock-security-billing-status'
            ),
        ));
        **/
    }
    

    /**
     * Gets site_parent_data
     *
     * @since    1.9.0
     */
    private function site_parent_data() {
        
        $this->wpslp_data = $this->api->get_word_quick( $this->sl_site_url() );
        $this->site_id    = $this->get_site_id();
        
        // get all features
        $this->get_features();
    }

    
    /**
     * Dashboard widget display
     *
     * @since    1.9.0
     */
    public function sl_dashboard_widget() {
        
        if ( $this->api->wpslp_options['auth_key'] || $this->api->wpslp_options['saml_key'] ) {

            if ( empty( $this->wpslp_data ) )
            {
                $this->site_parent_data();
            }
            
            include( 'partials/sitelock-admin-dashboard-widget.php' );
            
        } else {
            
            include( 'partials/sitelock-admin-connect.php' );
            
        }
        
    }


    /**
     * Add meta box to edit page
     * http://codex.wordpress.org/Function_Reference/add_meta_box
     */
    public function display_sitelock_scan_results() {

        if ( $this->api->wpslp_options['auth_key'] || $this->api->wpslp_options['saml_key'] ) {

            $screens = array( 'page' ); # post
        
            foreach ( $screens as $screen ) {
        
                add_meta_box(
                    'sitelock_scan_results',
                    __( 'SiteLock Security Status', 'security_scan_results' ),
                    array( $this, 'sitelock_myplugin_meta_box_callback' ),
                    $screen
                );
            }
        }
    }
    

    /**
     * Add meta box to edit page
     * 
     * @since   1.9.0
     * 
     * http://codex.wordpress.org/Function_Reference/add_meta_box
     */
    public function sitelock_myplugin_meta_box_callback( $post ) {
        $status_check = sitelock_is_malware_free( get_permalink( $post->ID ) );
        $scan_results = sitelock_decorate_scan_results( $status_check );
        $scan_result  = ( $status_check ? 'good ' : '' );
        $last_scanned = date( 'M jS, Y', strtotime( get_option( 'sitelock_last_scanned_at' ) ) );
        $scan_now     = admin_url( 'tools.php?page=sitelock&scan=site_scan&return_to_post=' . $post->ID );
        $protect_now  = admin_url( 'tools.php?page=sitelock&protect=' . $post->ID );

        // get data for waf and products
        if ( !isset( $this->display_built ) )
        {
            $this->build_display();
        }

        $has_waf = false;
        
        if ( isset( $this->waf[ 'status' ] ) && $this->waf[ 'status' ] == 'fully-configured' && !empty( $this->waf[ 'config' ][ 'has_2fa' ] ) )
        {
            // show page protect options
            $has_waf = true;

            $page_protect = get_post_meta( $post->ID, 'page_protect' );

            $page_protect = !empty( $page_protect[ 0 ] ) ? (string) $page_protect[ 0 ] : 'off';
        }

        $admin_url = admin_url( 'tools.php?page=' . $this->plugin_name );
        
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sitelock-admin-meta-box.js', array( 'jquery' ), $this->version, true );
        
        // get scan codes
        $smart_scan_code   = 'smart_scan';
        $malware_scan_code = 'site_scan';
        
        if ( !empty( $this->boxes ) ) 
        {
            foreach ( $this->boxes as $order => $data ) 
            {
                if ( strpos( $order, 'smart' ) !== false )
                {
                    if ( isset( $data[ 'scan_code' ] ) )
                    {
                        $smart_scan_code = $data[ 'scan_code' ];
                    }
                }
                
                if ( strpos( $order, 'malware' ) !== false )
                {
                    if ( isset( $data[ 'scan_code' ] ) )
                    {
                        $malware_scan_code = $data[ 'scan_code' ];
                    }
                }
            }
        }

        include( 'partials/sitelock-admin-meta-box.php' );
    }
    
    
    /**
     * Get all files from a folder
     *
     * @since 2.0.0
     * @param string $file The absolute path to a file or directory
     */
    public function sitelock_get_files( $file )
    {
        if ( is_dir( $file ) )
        {
            // hold off for now
            foreach ( glob( $file . '/*' ) as $sub_file )
            {
                $this->sitelock_get_files( $sub_file );
            }
        }
        else
        {
            if ( strpos( $file, '.zip' ) !== false || strpos( $file, 'smart_zips/slwp_page_' ) !== false )
            {
                // ignore this file
            }
            else
            {
                if ( !isset( $this->all_files ) )
                {
                    $this->all_files = array();
                }

                $this->all_files[] = $file;   
            }
        }
    }
    

    /**
     * Save post metadata when a post is saved.
     *
     * @since   2.0.0
     * 
     * @param   int     $post_id    The post ID.
     * @param   post    $post       The post object.
     * @param   bool    $update     Whether this is an existing post being updated or not.
     */
    function save_page_protect( $post_id, $post, $update ) {

        // If this isn't a 'page' post, don't update it.
        if ( $post->post_type != 'page' ) {
            return;
        }

        // Update page protect
        if ( isset( $_REQUEST[ 'sitelock_page_protect' ] ) && $_REQUEST[ 'sitelock_page_protect' ] != $_REQUEST[ 'sitelock_page_protect_current' ] ) {
            
            // get action
            $action = $_REQUEST[ 'sitelock_page_protect' ] == 'on' ? 'on' : 'off';

            // get current site
            if ( empty( $this->wpslp_data ) )
            {
                $this->site_parent_data();
            }

            // get url for this page
            $page_url = get_permalink( $post_id );

            // update API
            $this->api->update_page_protect( $this->site_id, $page_url );

            // update DB to save result
            update_post_meta( $post_id, 'page_protect', $action );
        }
        
        // Submit scan if needed
        if ( isset( $_REQUEST[ 'sitelock_scan_page_type' ] ) )
        {
            $this->api->post_queue_scan( $this->site_id, $_REQUEST[ 'sitelock_scan_page_type' ] );
        }
        
    }
    
    
    /** 
     * Returns the sitelock tools page
     * 
     * @since 2.2.0
     * @param string $page specific page within the tools page
     */
    private function sl_admin_url( $page = '' )
    {
        if ( $page == 'login' )
        {
            $page = '&logout=true';
        }
        
        return admin_url( 'tools.php?page=' . $this->plugin_name . $page );
    }
    

    /**
     * Displays tools page
     *
     * @since    1.9.0
     */
    public function main_options_page() {
        
        if ( $this->api->get_auth_key() ) { # && $this->api->is_auth_key_valid() ) {
            
            $this->status = $this->error = '';
            
            if ( empty( $this->wpslp_data ) )
            {
                $this->site_parent_data();
            }
            
            $admin_url = $this->sl_admin_url();
            $this->sitelock_sso = sitelock_sso();
            
            $this->update_malware_results();
            
            if ( !empty( $_GET[ 'scan' ] ) ) {

                $scan_type    = sanitize_title( sanitize_text_field( $_GET[ 'scan' ] ) );
                $scan_results = $this->api->post_queue_scan( $this->site_id, $scan_type );

                if ( !empty( $_GET[ 'return_to_post' ] ) && is_numeric( $_GET[ 'return_to_post' ] ) ) {
                    
                    // we have a return to post action
                    $post_id = sanitize_key( $_GET[ 'return_to_post' ] );
                    $admin_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

                }
                
                include( 'partials/sitelock-admin-scan-now.php' );

            } else if ( !empty( $_GET[ 'badge_settings' ] ) ) {
                
                $this->badge_settings();

                include( 'partials/sitelock-admin-badge-settings.php' );

            } else if ( !empty( $_GET[ 'waf_config' ] ) ) {
                
                $this->status = $this->error = '';
                
                $this->save_waf_traffic_routing();
                
                $this->save_waf_site_ip();
                
                $this->purge_specific();
                
                $this->purge_all_cache();
                
                $this->save_waf_cache_mode();
                
                $this->save_waf_cache_settings();
                
                $this->build_waf_config();
                
            } else if ( !empty( $_GET[ 'token' ] ) ) {
                
                ?>
                Token<br />
                <input type="text" value="<?php echo esc_attr( $this->api->get_auth_key() ); ?>" style="width: 100%;" />
                <br /><br />
                Site ID<br />
                <input type="text" value="<?php echo esc_attr( $this->site_id ); ?>" style="width: 100%;" />
                <?php
                
            } else if ( !empty( $_GET[ 'waf_setup' ] ) ) {
                
                $this->waf_setup();
                
            } else if ( !empty( $_GET[ 'smart_config' ] ) ) {
                
                $this->admin_url = $admin_url . '&smart_config=true';
                $this->smart_setup();
                
            } else if ( !empty( $_GET[ 'billing_status' ] ) ) {
                
                $this->admin_url = $admin_url . '&billing_status=true';
                
                include( 'partials/sitelock-admin-billing-status.php' );
                
            } else {

                // check for meta tag and site validation
                // if site not validated add meta tag to 
                // front end and then run validation process
                $meta_tag   = get_option( 'sitelock_meta_tag' );
                
                if ( empty( $meta_tag ) )
                {
                    $site_code  = $this->api->get_domain_validate_call( $this->site_id );
                    $meta_tag   = '<meta name="sitelock-site-verification" content="' . $site_code[ 'verification_data' ][ 'code' ] . '" />';

                    update_option( 'sitelock_meta_tag', $meta_tag );

                    $this->api->get_domain_validate_call( $this->site_id, 'ready' );
                }

                // define current page url with site option
                $admin_url = $admin_url . '&site=';

                // build site list for dropdown
                
                if ( isset( $_GET[ 'protect_login' ] ) )
                {
                    $action = sanitize_text_field( $_GET[ 'protect_login' ] ) == 'true' ? 'add' : 'delete';
                    
                    $login_page_url = trim( str_replace( $this->sl_site_url(), '', wp_login_url() ), '/' );
                    
                    $this->api->update_page_protect( $this->site_id, $login_page_url, $action );
                    
                    update_option( 'sitelock_login_authentication', ( $action == 'add' ? '1' : '0' ) );
                    
                    $login_protect_status = 'success';
                }
                
                $is_login_page_protected = get_option( 'sitelock_login_authentication' );

                // get data for waf and products
                if ( !isset( $this->display_built ) )
                {
                    $this->build_display();
                }

                // include the view file
                include( 'partials/sitelock-admin-display.php' );

                // check if cache needs to be reset
                $this->api->check_to_reset_cache();
            }

            // load javascript
            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sitelock-admin.js', array( 'jquery' ), $this->version, true );
            
        } else {
            $this->clear_site_id();

            // check for banner
            $this->banner = $this->api->banner();
            
            include( 'partials/sitelock-admin-connect.php' );
        }
    }
    
    
    /**
     * SMART Setup
     *
     * @since 2.0.0
     */
    public function smart_setup()
    {
        $this->zip_size = 0;
        
        $current_scan_id = $this->get_current_scan_id();
        
        if ( $current_scan_id )
        {
            // get scan status
            $status = $this->api->get_smart_single_scan_status( $current_scan_id, $this->site_id );
            
            $current_scan = array( 'status' => 'scanning', 'message' => $status );
        }
        
        $download_url = $this->smart_run();

        // get smart config/fix values
        $this->get_smart_settings();

        // only available for accounts with SMART
        if ( $this->can_fix )
        {
            if ( isset( $_POST[ 'ftp_type' ] ) )
            {
                $payload = array();
                $payload[ 'ftp_type'    ] = sanitize_text_field( $_POST[ 'ftp_type'           ] );
                $payload[ 'ftp_host'    ] = sanitize_text_field( $_POST[ 'ftp_host'           ] );
                $payload[ 'port'        ] = sanitize_text_field( $_POST[ 'ftp_port'           ] );
                $payload[ 'docroot'     ] = sanitize_text_field( $_POST[ 'ftp_root'           ] );
                $payload[ 'ftp_user'    ] = sanitize_text_field( $_POST[ 'ftp_user'           ] );
                $payload[ 'parallel'    ] = sanitize_text_field( $_POST[ 'ftp_download_speed' ] );
                $payload[ 'ftp_timeout' ] = sanitize_text_field( $_POST[ 'ftp_download_time'  ] );
                
                if ( !empty( $payload[ 'ftp_pass' ] ) )
                {
                    $payload[ 'ftp_pw' ] = sanitize_text_field( $_POST[ 'ftp_pass' ] );
                }
                
                $this->api->set_smart_settings( $this->site_id, $payload );
                
                $success_save_ftp = 'success';
            }
            
            $smart_settings = $this->api->get_smart_settings( $this->site_id );
        }

        include( 'partials/sitelock-admin-smart-setup.php' );
    }
    
    
    /**
     * Get Current Scan ID
     *
     * @since   2.0.0
     */
    public function get_current_scan_id()
    {
        $scan_id = get_option( 'sitelock_current_smart_scan_id' );
        
        return ( !empty( $scan_id ) ? $scan_id : false );
    }


    const DB_PAGE_DUMP_EXT = '.php';
    
    
    /**
     * SMART create zip
     *
     * @since 2.0.0
     * @param int $page_id WordPress page ID to be scanned
     *
     * @notes system('zip -P pass file.zip file.txt');
     */
    public function smart_run( $page_id = '' )
    {
        $total_pages_to_scan = 0;
        $get_pages = get_option( 'sl_smart_post_ids' );

        if ( is_array( $get_pages ) and !empty( $get_pages ) )
        {
            $total_pages_to_scan = count( $get_pages );
        }

        if ( isset( $_POST[ 'sl_submit_scan' ] ) || $total_pages_to_scan > 0 )
        {
            // Create ZIP
            $zip            = new Sitelock_Zip();
            $zip_file_name  = '/smartscan_' . date( 'Y_m_d_H_i_s' ) . '.zip';
            $plugins_url    = plugins_url() . '/' . $this->plugin_name . '/' . Sitelock_Zip::ZIP_FOLDER;
            $download_url   = $plugins_url . $zip_file_name;
            
            // get page content
            // if ( !empty( $_GET[ 'p' ] ) )
            if ( $total_pages_to_scan > 0 )
            {
                // reset option
                delete_option( 'sl_smart_post_ids' );

                foreach ( $get_pages as $page_id )
                {
                    $page = get_page( $page_id );
                    $content = $page->post_content;
                    
                    try {
                        $page_content_name = $zip->zip_location . '/slwp_page_' . $page_id . self::DB_PAGE_DUMP_EXT;
                        $create_page = fopen( $page_content_name, 'w' );
                        fwrite( $create_page, $content );
                        fclose( $create_page );
                        
                        if ( !isset( $this->all_files ) )
                        {
                            $this->all_files = array();
                        }
                        
                        $this->all_files[] = $page_content_name;
                    }
                    catch ( Exception $e )
                    {
                        
                    }
                }
            }

            // all plugins
            $directory = get_home_path() . 'wp-content/plugins';
            $this->sitelock_get_files( $directory );

            // all themes
            $directory = get_template_directory();
            $this->sitelock_get_files( $directory );
            
            $files_to_zip = $this->all_files;
            
            // calculate total size of all files combined
            $total_size = 0;
            foreach ( $files_to_zip as $file )
            {
                $total_size += filesize( $file );
            }
            
            $api_response = $this->api->post_smart_single_scan_init( $this->site_id, $total_size );
            
            if ( isset( $api_response[ 'status' ] ) && $api_response[ 'status' ] == 'ok' )
            {
                $zip->queue_id    = $api_response[ 'queue_id'   ];
                $zip->cipher      = $api_response[ 'cipher'     ];
                $zip->encrypt_key = $api_response[ 'cipher_key' ];
                $zip->iv          = urldecode( $api_response[ 'cipher_iv' ] );
                
                if ( empty( $zip->queue_id ) )
                {
                    return array( 'status' => 'error', 'code' => 'scanpending' );
                }
                
                if ( $zip->cipher != 'blowfish' )
                {
                    return array( 'status' => 'error', 'code' => 'noblowfish' );
                }
                
                update_option( 'sitelock_current_smart_scan_id', $zip->queue_id );
            }
            
            // run zip
            try {
                $zip_status = $zip->archive_files( $files_to_zip, $zip_file_name );
            }
            catch ( Exception $e )
            {
                $zip_status = false;
                #$return = array( 'status' => 'error', 'code' => 'servererror' );
            }
            
            if ( !empty( $return ) )
            {
                return $return;
            }
            
            if ( !$zip_status )
            {
                return array( 'status' => 'error', 'code' => 'nosupport' );
            }
            
            // get zip filesize
            $this->zip_size = $total_size;
            
            if ( $this->zip_size > 0 )
            {
                $response = $this->api->post_smart_single_scan_queue( $this->site_id, $zip->queue_id, $download_url );
                
                return array( 'status' => 'success', 'file' => $download_url );
            }
        }
        
        return false;
    }
    
    
    /**
     * Save WAF Traffic Routing
     *
     * @since 2.0.0
     */
    private function save_waf_traffic_routing() {
        
        if ( isset( $_POST[ 'bypass' ] ) ) {
            
            $bypass          = sanitize_text_field( $_POST[ 'bypass' ] ) === '1' ? '1' : '0';
            $use_true_speed  = isset( $_POST[ 'use_true_speed'  ] ) && sanitize_text_field( $_POST[ 'use_true_speed'  ] ) === '1' ? '1' : '0';
            $use_true_shield = isset( $_POST[ 'use_true_shield' ] ) && sanitize_text_field( $_POST[ 'use_true_shield' ] ) === '1' ? '1' : '0';

            $response = $this->api->set_waf_general_settings( $this->site_id, $bypass, $use_true_shield, $use_true_speed );
            
            $this->status = 'Traffic routing saved';
        }
    }
    
    
    /**
     * Save Site IP
     *
     * @since 2.0.0
     */
    private function save_waf_site_ip() {
        
        if ( isset( $_POST[ 'new_ip' ] ) ) {
            
            $new_ip = sanitize_text_field( $_POST[ 'new_ip' ] );
            
            if ( !filter_var( $new_ip, FILTER_VALIDATE_IP ) === false ) {
                
                // valid IP
                $response = $this->api->set_site_ip( $this->site_id, $new_ip );
                $this->status = 'Site IP Saved';
                
            } else {
                
                // invalid IP
                $this->error = 'Invalid IP';
                
            }
        }
    }
    
    
    /**
     * Save
     *
     * @since 2.0.0
     */
    private function purge_specific() {
        
        if ( isset( $_POST[ 'page_id' ] ) )
        {
        
            $page_id  = (int) sanitize_text_field( $_POST[ 'page_id' ] );
            $page_url = str_replace( array( 'http://', 'https://', $this->site_url ), '', get_permalink( $page_id ) );
            $this->api->set_purge_cache( $this->site_id, $page_url );
            $this->status = 'Cache purged';
            
        }
    }
    
    
    /**
     * Purge all cache
     *
     * @since 2.0.0
     */
    private function purge_all_cache() {
        
        if ( isset( $_POST[ 'sl_purge_cache' ] ) ) {
            
            $this->api->set_purge_cache( $this->site_id );
            $this->status = 'All cache purged';
            
        }
        
    }
    
    
    /**
     * Save WAF Cache Mode
     * 
     * @since 2.0.0
     */
    private function save_waf_cache_mode() {
        
        if ( isset( $_POST[ 'cache_mode' ] ) ) {
            
            $cache_mode = $this->sanitize_title( sanitize_text_field( $_POST[ 'cache_mode' ] ), '_' );
            $this->api->build_cdn_payload( $this->site_id, array( 'cache_mode' => $cache_mode ) );
            $this->status = 'Cache mode saved';
            
        }
        
    }
    
    
    /**
     * Save WAF Cache Settings
     *
     * @since 2.0.0
     */
    private function save_waf_cache_settings() {
        
        if ( isset( $_POST[ 'save_cache_settings' ] ) ) {
         
            $async_validation            = !empty( $_POST[ 'async_validation'            ] ) ? '1' : '0';
            $minify_javascript           = !empty( $_POST[ 'minify_javascript'           ] ) ? '1' : '0';
            $minify_css                  = !empty( $_POST[ 'minify_css'                  ] ) ? '1' : '0';
            $minify_static_html          = !empty( $_POST[ 'minify_static_html'          ] ) ? '1' : '0';
            $compress_jpeg               = !empty( $_POST[ 'compress_jpeg'               ] ) ? '1' : '0';
            $compress_png                = !empty( $_POST[ 'compress_png'                ] ) ? '1' : '0';
            $on_the_fly_compression      = !empty( $_POST[ 'on_the_fly_compression'      ] ) ? '1' : '0';
            $tcp_pre_pooling             = !empty( $_POST[ 'tcp_pre_pooling'             ] ) ? '1' : '0';    
            $comply_no_cache             = !empty( $_POST[ 'comply_no_cache'             ] ) ? '1' : '0';
            $comply_vary                 = !empty( $_POST[ 'comply_vary'                 ] ) ? '1' : '0';
            $use_shortest_caching        = !empty( $_POST[ 'use_shortest_caching'        ] ) ? '1' : '0';
            $prefer_last_modified        = !empty( $_POST[ 'prefer_last_modified'        ] ) ? '1' : '0';
            $accelerate_https            = !empty( $_POST[ 'accelerate_https'            ] ) ? '1' : '0';
            $disable_client_side_caching = !empty( $_POST[ 'disable_client_side_caching' ] ) ? '1' : '0';
            $aggressive_compression      = !empty( $_POST[ 'aggressive_compression'      ] ) ? '1' : '0';
            $progressive_image_rendering = !empty( $_POST[ 'progressive_image_rendering' ] ) ? '1' : '0';
            
            $cache_settings = array(
                'async_validation'              => $async_validation,
                'minify_javascript'             => $minify_javascript,
                'minify_css'                    => $minify_css,
                'minify_static_html'            => $minify_static_html,
                'compress_jpeg'                 => $compress_jpeg,
                'compress_png'                  => $compress_png,
                'on_the_fly_compression'        => $on_the_fly_compression,
                'tcp_pre_pooling'               => $tcp_pre_pooling,
                'comply_no_cache'               => $comply_no_cache,
                'comply_vary'                   => $comply_vary,
                'use_shortest_caching'          => $use_shortest_caching,
                'prefer_last_modified'          => $prefer_last_modified,
                'accelerate_https'              => $accelerate_https,
                'disable_client_side_caching'   => $disable_client_side_caching,
                'aggressive_compression'        => $aggressive_compression,
                'progressive_image_rendering'   => $progressive_image_rendering
            );
            
            $this->api->build_cdn_payload( $this->site_id, $cache_settings );
            $this->status = 'Cache settings saved';
        }
    }
    
    
    /**
     * Call to get waf info
     *
     * @since 2.0.0
     */
    private function waf_info()
    {
        return $this->api->get_waf_general_info( $this->site_id );
    }
    
    
    /**
     * Build WAF config
     *
     * @since 2.0.0
     */
    private function build_waf_config() 
    {    
        $waf_info = $this->waf_info();

        if ( isset( $waf_info[ 'site_status' ] ) && $waf_info[ 'site_status' ] == 'fully-configured' ) 
        {    
            $general_setting = array();
            $general_setting[ 'bypass'          ] = ( isset( $waf_info[ 'general_settings' ][ 'bypass' ] ) ? $waf_info[ 'general_settings' ][ 'bypass' ] : '' );
            $general_setting[ 'use_true_speed'  ] = ( isset( $waf_info[ 'general_settings' ][ 'use_true_speed'  ] ) ? $waf_info[ 'general_settings' ][ 'use_true_speed'  ] : '' );
            $general_setting[ 'use_true_shield' ] = ( isset( $waf_info[ 'general_settings' ][ 'use_true_shield' ] ) ? $waf_info[ 'general_settings' ][ 'use_true_shield' ] : '' );
            
            $dns_orig_a     = ( isset( $waf_info[ 'dns' ][ 'dns_orig_a'     ] ) ? explode( ',', $waf_info[ 'dns' ][ 'dns_orig_a' ] ) : array() );
            $dns_new_a      = ( isset( $waf_info[ 'dns' ][ 'dns_new_a'      ] ) ? explode( ',', $waf_info[ 'dns' ][ 'dns_new_a'  ] ) : array() );
            
            $dns_orig_cname = ( isset( $waf_info[ 'dns' ][ 'dns_orig_cname' ] ) ? $waf_info[ 'dns' ][ 'dns_orig_cname' ] : '' );
            $dns_new_cname  = ( isset( $waf_info[ 'dns' ][ 'dns_new_cname'  ] ) ? $waf_info[ 'dns' ][ 'dns_new_cname'  ] : '' );
            
            $site_ip        = ( isset( $waf_info[ 'origin_ip' ][ 'ip' ][ 0 ] ) ? $waf_info[ 'origin_ip' ][ 'ip' ][ 0 ] : '' );
            
            $ssl_detected   = !empty( $waf_info[ 'ssl' ][ 'detected'  ] ) ? true : false;
            $ssl_installed  = !empty( $waf_info[ 'ssl' ][ 'installed' ] ) ? true : false;
            
            $cdn_settings = $this->api->get_waf_cdn_info( $this->site_id );
            
            $async_validation            = !empty( $cdn_settings[ 'cdnSettings' ][ 'async_validation'            ] ) ? 'checked' : '';
            $minify_javascript           = !empty( $cdn_settings[ 'cdnSettings' ][ 'minify_javascript'           ] ) ? 'checked' : '';
            $minify_css                  = !empty( $cdn_settings[ 'cdnSettings' ][ 'minify_css'                  ] ) ? 'checked' : '';
            $minify_static_html          = !empty( $cdn_settings[ 'cdnSettings' ][ 'minify_static_html'          ] ) ? 'checked' : '';
            $compress_jpeg               = !empty( $cdn_settings[ 'cdnSettings' ][ 'compress_jpeg'               ] ) ? 'checked' : '';
            $compress_png                = !empty( $cdn_settings[ 'cdnSettings' ][ 'compress_png'                ] ) ? 'checked' : '';
            $on_the_fly_compression      = !empty( $cdn_settings[ 'cdnSettings' ][ 'on_the_fly_compression'      ] ) ? 'checked' : '';
            $tcp_pre_pooling             = !empty( $cdn_settings[ 'cdnSettings' ][ 'tcp_pre_pooling'             ] ) ? 'checked' : '';    
            $comply_no_cache             = !empty( $cdn_settings[ 'cdnSettings' ][ 'comply_no_cache'             ] ) ? 'checked' : '';
            $comply_vary                 = !empty( $cdn_settings[ 'cdnSettings' ][ 'comply_vary'                 ] ) ? 'checked' : '';
            $use_shortest_caching        = !empty( $cdn_settings[ 'cdnSettings' ][ 'use_shortest_caching'        ] ) ? 'checked' : '';
            $prefer_last_modified        = !empty( $cdn_settings[ 'cdnSettings' ][ 'prefer_last_modified'        ] ) ? 'checked' : '';
            $accelerate_https            = !empty( $cdn_settings[ 'cdnSettings' ][ 'accelerate_https'            ] ) ? 'checked' : '';
            $disable_client_side_caching = !empty( $cdn_settings[ 'cdnSettings' ][ 'disable_client_side_caching' ] ) ? 'checked' : '';
            $aggressive_compression      = !empty( $cdn_settings[ 'cdnSettings' ][ 'aggressive_compression'      ] ) ? 'checked' : '';
            $progressive_image_rendering = !empty( $cdn_settings[ 'cdnSettings' ][ 'progressive_image_rendering' ] ) ? 'checked' : '';
            $cache_mode                  = !empty( $cdn_settings[ 'cdnSettings' ][ 'cache_mode'                  ] ) ? $cdn_settings[ 'cdnSettings' ][ 'cache_mode'                  ] : '';
            $cache_mode_formal           = $this->cache_mode_alt_text( $cache_mode );
            
            // cache modes
            $cache_modes_array = isset( $cdn_settings[ 'options' ][ 'cache_modes' ][ 'cache_mode' ] ) ? $cdn_settings[ 'options' ][ 'cache_modes' ][ 'cache_mode' ] : array();
            $cache_modes = array();
            
            foreach ( $cache_modes_array as $mode )
            {
                $cache_modes[ $mode ] = $this->cache_mode_alt_text( $mode );
            }
            
            // tooltips
            $async_tooltip                  = "Async validation controls the transition between cached copies that occurs once the caching period has expired.<br><br>With Async validation off, the first request of each caching cycle is served from the origin server, which ensures freshness at the cost of load speed. With Async validation on, the first request is served from cache, which will update asymmetrically as the request is being processed. This impacts freshness but provides better time-to-first-byte (TTFB) and improves overall performance.";
            $content_minification_tooltip   = "Minification reduces file size by removing unnecessary characters (such as whitespace and comments) to decrease access time without impacting functionality. Using these acceleration controls you can choose the type of resources you wish to minify - JS, CSS or static HTML files.<br><br>Please note: These content minification settings will have no effect when caching is disabled. ";
            $image_compression_tooltip      = "Image compression can be applied to JPEG and PNG images to reduce load times. With compression is enabled, image metadata is omitted and the image data itself is compressed. In certain cases this may affect image quality.";
            $jpeg_progressive_tooltip       = "The image appears faster and is rendered with progressively finer resolution, for additional compression. During the rendering stage the image may appear 'pixelated' but the final version will appear with no loss of quality.";
            $jpeg_aggressive_tooltip        = "Applies a more aggressive compression algorithm. Selecting this option will further reduce the image file size and its loading time, but at the cost of decreased image quality.";
            $on_the_fly_compression_tooltip = "When this option is enabled, text files (JS, CSS and HTML) are gzipped before being transferred to reduce loading times. Gzipping is a common best practice which significantly accelerates page load speeds, reducing the compressed file sizes by 30%-40% or more, depending on the size and type of the file. For this reason, “On the Fly” compression is always advised and is most effective for larger resources.";
            $tcp_pre_pooling_tooltip        = "By default, a web session may open and close many TCP connections dynamically throughout the session, causing a certain overhead and reducing performance. With TCP Pre-Pooling enabled, we maintain several TCP connections constantly open throughout the session, thereby improving load speeds and connectivity.";
            
            $status = $this->status != '' ? $this->status : '';
            $error  = $this->error  != '' ? $this->error  : '';
            
            include( 'partials/sitelock-admin-waf-config.php' );
        
        } else {
            $this->waf_setup();
        }
    }
    
    
    /**
     * WAF Setup / Wizard
     *
     * @since 2.0.0
     */
    public function waf_setup()
    {   
        include( 'partials/sitelock-admin-waf-setup.php' );
    }
    

    /**
     * Save and retrieve badge settings
     *
     * @since    1.9.0
     */
    public function badge_settings()
    {
        $this->site_url = $this->sl_site_url();
        $this->status = false;

        $badge_location = sanitize_key( $_POST[ 'sitelock_badge_location' ] );
        $badge_color    = sanitize_key( $_POST[ 'sitelock_badge_color' ] );
        $badge_size     = sanitize_key( $_POST[ 'sitelock_badge_size' ] );
        $badge_type     = sanitize_key( $_POST[ 'sitelock_badge_type' ] );
        
        if ( !empty( $badge_location ) )
        {
            update_option( 'sitelock_badge_location', $badge_location ); // badge location
            update_option( 'sitelock_badge_color',    $badge_color ); // color
            update_option( 'sitelock_badge_size',     $badge_size ); // size
            update_option( 'sitelock_badge_type',     $badge_type ); // type
            
            if (
                    $badge_size  && in_array( $badge_size,  array( 'small', 'medium', 'big' ) )
                &&  $badge_color && in_array( $badge_color, array( 'white', 'red' ) )
                &&  $badge_type  && in_array( $badge_type,  array( 'malware-free', 'secure' ) )
                &&  get_option( 'sitelock_site_id' )
            ) {
                $type = $badge_type == 'malware-free' ? 'mal_04' : 'secure_04';
                $badge_type = join('_', array(
                        strtolower( $badge_size ),
                        strtolower( $badge_color ),
                        'en',
                        $type
                    )
                );
                
                $response = $this->api->update_badge_settings( get_option( 'sitelock_site_id'), $badge_type );

                if ( is_array( $response ) && $response['link'] && $response['img'] ) 
                {
                    update_option( 'sitelock_badge_link', $response['link'] );
                    update_option( 'sitelock_badge_img',  $response['img'] );
                }
            }
            
            $this->status = 'Settings Saved. It may take up to fifteen minutes for the badge settings to update on your website.'; 
        }
        
        $this->current_badge_location = get_option( 'sitelock_badge_location' );
        $this->current_badge_color    = get_option( 'sitelock_badge_color'    );
        $this->current_badge_size     = get_option( 'sitelock_badge_size'     );
        $this->current_badge_type     = get_option( 'sitelock_badge_type'     );
    }
    
    
    /**
     * Alternative Text
     *
     * @since 2.0.0
     * @param string $text The text to be changed
     */
    public function cache_mode_alt_text( $text )
    {
        $result = $text;
        
        switch( $text )
        {
            case 'disable':
                $result = 'Disable caching';
                break;
            
            case 'static_only':
                $result = 'Cache static content only';
                break;
            
            case 'static_and_dynamic':
                $result = 'Cache static and dynamic content';
                break;
        }
        
        return $result;
    }
    
    
    /**
     * Build features array from API
     *
     * @since 2.0.0
     */
    public function get_features()
    {
        $this->features = 
        $this->groups   = array();
        
        $secure = 'green';

        if ( !empty( $this->wpslp_data[ 'groups' ] ) && is_array( $this->wpslp_data[ 'groups' ] ) && !empty( $this->wpslp_data[ 'products' ][ 'items' ] ) && is_array( $this->wpslp_data[ 'products' ][ 'items' ] ) )
        {
            foreach ( $this->wpslp_data[ 'groups' ] as $key => $data ) 
            {    
                $feature_type = sanitize_title( $data[ 'name' ] );
                $group_id     = $data[ 'id' ];
                $features     = array();
                
                // get all features for this id
                if ( $group_id == '1' )
                {
                    // this is for waf
                    if ( !empty( $this->wpslp_data[ 'waf' ][ 'purchased' ] ) )
                    {
                        $features[ 'waf' ] = $this->wpslp_data[ 'waf' ];    
                    }
                    else
                    {
                        // no waf so do not add to group
                        continue;
                    }
                }
                else
                {
                    foreach ( $this->wpslp_data[ 'products' ][ 'items' ] as $code => $product )
                    {
                        if ( $product[ 'group' ] == $group_id )
                        {
                            $features[ $code ] = $product;
                        }
                    }
                }

                // build object array
                if ( !empty( $features ) )
                {
                    // check for site status
                    if ( $secure == 'green' )
                    {
                        // since site status is green, we will only change
                        // the status if this feature is yellow or red 
                        $secure = $data[ 'ryg' ] == 'yellow' ? 'yellow' : ( $data[ 'ryg' ] == 'red' ? 'red' : $secure ); 
                    }
                    else if ( $secure == 'yellow' )
                    {
                        // since site status is yellow, we will only change 
                        // the status if this feature is red
                        $secure = $data[ 'ryg' ] == 'red' ? 'red' : $secure; 
                    }

                    // change name of group
                    $name = $data[ 'name' ];

                    switch ( $feature_type )
                    {
                        case 'prevention':
                            $name = 'Traffic';
                            $sub_name = 'Traffic: TrueShield';
                            break;
                        
                        case 'application':
                        case 'infrastructure':
                            $name = 'Code';
                            $sub_name = $name;
                            break;

                        case 'content':
                            $name = 'Content';
                            $sub_name = $name;
                            break;

                        case 'reputation':
                            $name = 'Reputation';
                            $sub_name = $name;
                            break;
                    }

                    $this->groups[ $group_id ] = array(
                        'name' => $name,
                        'sub_name' => $sub_name, 
                        'type' => $feature_type,
                        'attention_flag' => $data[ 'ryg' ],
                        'message' => $data[ 'ryg' ] == 'green' ? 'fully-configured' : 'pending', 
                        'features' => $features
                    );
                }
            }
        }

        if ( isset( $this->wpslp_data[ 'risk' ] ) )
        {
            $risk = $this->wpslp_data[ 'risk' ];
            $risk[ 'score' ] = $risk[ 'score' ] == '1' ? 'low' : ( $risk[ 'score' ] == '2' ? 'medium' : 'high' );

            $risk_feature = array();
            
            foreach ( $risk[ 'determinants' ] as $name => $score ) 
            {
                $risk_feature[ $name ] = array( 
                    'display' => ucfirst( $name ), 
                    'score' => $score . '%'
                );
            }

            $risk_feature[ 'score' ] = array( 
                'display' => 'Total Risk Score', 
                'score' => $risk[ 'score' ]
            );

            $this->risk_score = array(
                'name' => 'Risk Score', 
                'type' => 'risk', 
                'attention_flag' => '', 
                'message' => 'fully-configured', 
                'features' => $risk_feature
            );
        }
        
        // check if site_parent_data object is available
        if ( !isset( $this->site_parent_data ) )
        {
            $this->site_parent_data = array();
        }
        
        // secure status
        $this->site_parent_data[ 'message'        ] = $secure == 'green' ? 'secure' : $secure == 'not secure';
        $this->site_parent_data[ 'attention_flag' ] = $secure;
        $this->site_parent_data[ 'status'         ] = 'active';

        $this->config = 
        $this->alert  = array();

        // check for config wizard
        foreach ( $this->groups as $group_id => $group_data )
        {
            // features
            foreach ( $group_data[ 'features' ] as $slug => $val )
            {
                if ( isset( $val[ 'status' ] ) && ( $val[ 'status' ] == 'noncompliant' || $val[ 'ryg' ] == 'red' ) )
                {
                    // add to alert wizard
                    $this->alert[ $slug ] = $val;
                }
                else if ( isset( $val[ 'configured' ] ) && (string) $val[ 'configured' ] == 'false' )
                {
                    // error message
                    $val[ 'sync_message' ] = 'Requires configuration before scans can be scheduled.';
                    $val[ 'url' ] = $slug == 'smart' ? 'wizard/smart' : $slug;

                    // add to config wizard
                    $this->config[ $slug ] = $val;
                }
                else if ( $slug == 'domain_email' && $val[ 'status' ] != 'verified' )
                {
                    // sync message
                    $val[ 'sync_message' ] = 'Verification is required to submit some scans.';

                    // add to config wizard
                    $this->config[ $slug ] = $val;
                }
            }
        }
    }
    
    
    /**
     * Get SMART feature
     *
     * @since 2.0.0
     * @param string $string Return as string or boolean
     */
    public function get_smart_feature_status( $string_or_boolean = 'boolean' )
    {
        $smart = false;
    
        if ( !isset( $this->features ) )
        {
            $this->get_features();
        }

        if ( isset( $this->features[ 'content' ][ 'feature' ][ 'smart' ][ 'ryg' ] ) )
        {
            if ( $string_or_boolean == 'boolean' )
            {
                $smart = false;
                
                if ( $this->features[ 'content' ][ 'feature' ][ 'smart' ][ 'ryg' ] == 'green' )
                {
                    $smart = true;
                }
            }
            else
            {
                $smart = $this->features[ 'content' ][ 'feature' ][ 'smart' ][ 'ryg' ];
            }
        }
        
        return $smart;
    }
    
    
    /**
     * Build display of waf and products
     *
     * @since    1.9.0
     */
    public function build_display()
    {
        $this->banner = '';
        $features = $this->waf = $this->boxes = array();

        if ( !empty( $this->groups ) ) 
        {
            $this->banner = $this->api->banner();

            if ( isset( $this->groups[ 1 ][ 'features' ][ 'waf' ] ) )
            {
                $nav     = 
                $body    = 
                $extras  = '';
                $c       = 
                $box_cnt = 0;

                $this->cache_data           = 
                $this->cache_requests       = 
                $this->chart_data           = 
                $this->chart_data_country   = 
                $this->chart_data_other     = false;
            
                $waf = $this->groups[ 1 ][ 'features' ][ 'waf' ];
                
                if ( isset( $waf[ 'status' ] ) && $waf[ 'status' ] != 'fully-configured' ) 
                {    
                    // waf is not configured or pending
                    $this->waf[ 'status'  ] = 'pending';
                    $this->waf[ 'message' ] = 'WAF is not fully configured.';
                    $this->waf[ 'details' ] = ucfirst( str_replace( '-', ' ', esc_html( $waf[ 'status' ] ) ) );
                    $this->waf[ 'config'  ] = isset( $waf[ 'config' ] ) ? $waf[ 'config' ] : '';
                } 
                else if ( isset( $waf[ 'status' ] ) ) 
                {
                    // waf configured
                    $this->waf[ 'status'  ] = $waf[ 'status' ];
                    $this->waf[ 'data'    ] = array();
                    $this->waf[ 'config'  ] = isset( $waf[ 'config' ] ) ? $waf[ 'config' ] : '';
                    
                    // Cache data for Graph
                    if ( !empty( $waf[ 'stats' ][ 'caching' ] ) ) {
                        foreach ( $waf[ 'stats' ][ 'caching' ] as $name => $val ) {
                            $this->$name = $val;
                        }
                        
                        $this->cache_data     = true;
                        $this->cache_requests = true;
                    }
                    
                    // Visitor data for Graph
                    if ( !empty( $waf[ 'stats' ][ 'visits' ][ 'human' ] ) ) {
                        
                        $human_stats = array();
                        $bot_stats   = array();
                        
                        foreach ( $waf[ 'stats' ][ 'visits' ][ 'human' ] as $stats_data )
                        {
                            $key = $stats_data[ 'ts'    ];
                            $val = $stats_data[ 'count' ];
                            
                            $human_stats[ $key ] = $val;
                        }
                        
                        foreach ( $waf[ 'stats' ][ 'visits' ][ 'bot' ] as $stats_data )
                        {
                            $key = $stats_data[ 'ts'    ];
                            $val = $stats_data[ 'count' ];
                            
                            $bot_stats[ $key ] = $val;
                        }
                        
                        ksort( $human_stats );
                        
                        $c = 0;
                        
                        foreach ( $human_stats as $date => $visits ) 
                        {
                            ++$c;
                            
                            if ( $c > 1 ) 
                            {
                                $human = (float) $visits;
                                $bots  = (float) $bot_stats[ $date ];
                                $date  = date( 'm/d', $date );
                                
                                $this->chart_data = true;
                                
                                $this->waf[ 'data' ][] = '<input type="hidden" class="chart_data" data-y="' . $date . '" data-a="' . $human . '" data-b="' . $bots . '" />';
                            }
                        }
                    }
                    
                    // Country data for Graph
                    if ( !empty( $waf[ 'stats' ][ 'visits_by_country' ] ) ) {
                        foreach ( $waf[ 'stats' ][ 'visits_by_country' ] as $country => $visits ) {
                            if ( !empty( $country ) ) {
                                $this->chart_data_country = true;
                                $this->waf[ 'data' ][] = '<input type="hidden" class="country_visits" data-label="' . sitelock_country_code_to_country( $country ) . '" data-value="' . (float) $visits . '" />';
                            }
                        }
                    }
                    
                    // Other data for Graph
                    if ( !empty( $waf[ 'stats' ][ 'visits_by_app' ] ) ) {
                        foreach ( $waf[ 'stats' ][ 'visits_by_app' ] as $client => $visits ) {                            
                            if ( !empty( $client ) ) {
                                $this->chart_data_other = true;
                                $this->waf[ 'data' ][] = '<input type="hidden" class="bot_visits" data-label="' . addslashes( esc_html( $client ) ) . '" data-value="' . (float) $visits . '" />';
                            }
                        }
                    }
                }
            }

            $this->product_box();
            // @todo check for features
        }
        else
        {
            // check for banner
            $this->banner = $this->api->banner();

            // need to re-connect
            $msg = 'You do not have any supported products';
            include( 'partials/sitelock-admin-logout.php' );
        }
        
        $this->display_built = true;
    }
    
    
    /** 
     * Date format
     * 
     * @since 2.1.1
     * @param string $date
     */
    private function date_format( $date )
    {
        return date( 'M jS, Y', strtotime( $date ) );
    }
    

    /**
     * Update malware results in wp_options
     *
     * @since    1.9.0
     */
    public function product_box() {
            
        $feature_array = array(
            'last_good_date'   => 'Last Good Scan',
            'date'             => 'Last Date Scanned',
            'total'            => 'Total Scanned',
            'count'            => 'Issue Count'
        );
        
        $extras = '';

        foreach ( $this->groups as $group_id => $group_data ) {

            if ( !empty( $group_data[ 'features' ] ) )
            {
                $boxes = array();

                foreach ( $group_data[ 'features' ] as $slug => $feature )
                {
                    $name      = isset( $feature[ 'display' ] ) ? esc_html( $feature[ 'display' ] ) : '';
                    $name_slug = sanitize_title( $slug );
                    $message   = strtolower( esc_html( $feature[ 'status' ] ) );
                    $group     = isset( $feature[ 'group' ] ) ? esc_html( $feature[ 'group' ] ) : '';
                    $scan_code = isset( $feature[ 'scan_code' ] ) ? $feature[ 'scan_code' ] : '';
                    $configured= isset( $feature[ 'configured' ] ) ? ( empty( $feature[ 'configured' ] ) || ( !empty( $feature[ 'configured' ] ) && $feature[ 'configured' ] == 'false' ) ? false : true ) : true;
                    $code      = !$configured ? 'Configure' : ucwords( esc_html( $feature[ 'status' ] ) );
                    $color     = isset( $feature[ 'ryg' ] ) ? esc_html( $feature[ 'ryg' ] ) : '';
                    $icon      = $color; # plugins_url( 'images/' . ( $color == 'green' ? 'good' : ( $color == 'yellow' ? 'pending' : 'failed' ) ) . '.png', __FILE__ );
                    $order     = ( $color == 'red' ? '1' : ( $color == 'yellow' ? '2' : '3' ) ) . '_' . $name_slug;
                    $resolve   = $color == 'red' ? true : false;
                    $config_url= '';
                    $scan      = true;
                    $scanned   = !empty( $feature[ 'last_scanned_at' ] ) ? date( 'F jS', strtotime( $feature[ 'last_scanned_at' ] ) ) : null;
                    $details   = '';
                    
                    // if ( $color == 'green' )
                    // {
                    //     $scanned = 'Last scan: ' . $this->date_format( ( !empty( $feature[ 'verified_at' ] ) ? $feature[ 'verified_at' ] : $feature[ 'last_scanned_at' ] ) );
                    // }
                    // else if ( !empty( $feature[ 'last_scanned_at' ] ) || !empty( $feature[ 'verified_at' ] ) )
                    // {
                    //     $scanned  = !empty( $feature[ 'verified_at' ] ) ? 'Last good scan: ' . $this->date_format( $feature[ 'verified_at' ] ) : '';
                    //     $scanned .= ( $scanned != '' ? '<br />' : '' ) . ( !empty( $feature[ 'last_scanned_at' ] ) ? 'Last scan: ' . $this->date_format( $feature[ 'last_scanned_at' ] ) : '' );
                    // }

                    switch ( $name_slug )
                    {
                        case 'address':
                        case 'phone':
                        case 'domain_email':
                            $scan = false;
                            break;
                        
                        case 'smart':
                        case 'smart-scan':
                            if ( isset( $feature[ 'sync_message' ] ) )
                            {
                                $message    = 'Error';
                                $code       = 'Error';
                                $configured = false;
                                $resolve    = false;
                                $details    = $feature[ 'sync_message' ];
                            }
                            
                            if ( !$configured )
                            {
                                $config_url = admin_url( 'tools.php?page=' . $this->plugin_name . '&smart_config=true' );
                            }
                            break;
                    }

                    $boxes[ $order ][ 'slug'       ] = $name_slug;
                    $boxes[ $order ][ 'color'      ] = $color;
                    $boxes[ $order ][ 'icon'       ] = $icon;
                    $boxes[ $order ][ 'code'       ] = $code;
                    $boxes[ $order ][ 'name'       ] = $name;
                    $boxes[ $order ][ 'scanned'    ] = $scanned;
                    $boxes[ $order ][ 'scan'       ] = $scan;
                    $boxes[ $order ][ 'resolve'    ] = $resolve;
                    $boxes[ $order ][ 'configured' ] = $configured;
                    $boxes[ $order ][ 'config_url' ] = $config_url;
                    $boxes[ $order ][ 'scan_code'  ] = $scan_code;
                    $boxes[ $order ][ 'details'    ] = $details;
                    $boxes[ $order ][ 'description'] = $this->product_description( $name_slug );
                    $boxes[ $order ][ 'group'      ] = $group;
                    $boxes[ $order ][ 'auto_run'   ] = isset( $feature[ 'auto_run' ] ) ? $feature[ 'auto_run' ] : false;
                    $boxes[ $order ][ 'manual_run' ] = isset( $feature[ 'manual_run' ] ) ? $feature[ 'manual_run' ] : false;
                }

                // arrange in order of importance
                ksort( $boxes );

                $boxes_formatted = array();

                foreach ( $boxes as $order => $data )
                {
                    $slug = substr( $order, 2 );
                    $boxes_formatted[ $slug ] = $data;
                } 

                // add to groups object
                $this->groups[ $group_id ][ 'boxes' ] = $boxes_formatted;
            }
        }
    }


    /** 
     * Define product descriptions
     * 
     * @since   3.5.0
     * @param   string  $slug 
     */
    private function product_description( $slug = '' )
    {
        switch ( $slug )
        {
            case 'smart';
                $desc = 'Scans your source code for malware.';
                break;

            case 'malware';
                $desc = 'Scans your website (the way it would be rendered to a browser) for malware.';
                break;

            case 'appscan';
                $desc = 'Scans your source code application for vulnerabilities.';
                break;

            case 'sql_injection';
                $desc = 'Scans your website for SQL injection vulnerabilities.';
                break;

            case 'xss';
                $desc = 'Scans your website for cross-site scripting vulnerabilities.';
                break;

            case 'address';
                $desc = 'Shows your visitors you have a location.';
                break;

            case 'phone';
                $desc = 'Shows your visitors you have a working phone line.';
                break;

            case 'sslcert';
                $desc = 'Looks for vulnerabilities in your SSL connection.';
                break;

            case 'domain_email';
                $desc = 'Confirms you own your domain which is required for some scans to run.';
                break;

            case 'spam';
                $desc = 'Scans your site for spam.';
                break;

            case 'db_scan':
                $desc = 'Scans your WordPress database for malware.';
                break;

            case 'platform_scan':
                $desc = 'Scans your platform for vulnerabilities.';
                break;

            case 'network_scan':
                $desc = 'Scans your network for vulnerabilities.';
                break;

            default;
                $desc = '';
        }

        return $desc;
    }

    
    /**
     * SL Site Url
     *
     * @since 2.0.0
     */
    public function sl_site_url()
    {
        return site_url();
    }
    

    /**
     * Clear Site ID from wp_options
     *
     * @since    1.9.0
     */
    public function clear_site_id()
    {
        update_option( 'sitelock_site_id', '' );
    }
    

    /**
     * Get Site ID
     *
     * @since    1.9.0
     * @param    array     $data
     */
    public function get_site_id( $data = array() ) {
        
        if ( !empty( $this->wpslp_data[ 'site' ][ 'id' ] ) )
        {
            $site_id = $this->wpslp_data[ 'site' ][ 'id' ];
            
            // save the site id
            update_option( 'sitelock_site_id', $site_id );
            
            return $site_id;
        }
        else
        {
            #header( 'Location: ' . $this->sl_admin_url( 'logout' ) );
            #exit;
        }
    }
    

    /**
     * Update malware results in wp_options
     *
     * @since    1.9.0
     * @param    int    $site_id
     */
    public function update_malware_results() {
        $response = $this->api->get_malware_scan_results( $this->site_id );

        if ( $response && is_array( $response ) ) {
            $infected_urls = array();

            foreach ( $response[ 'malware_results' ][ 'items' ] as $item ) {

                if ( $item[ 'url' ] ) {
                    array_push( $infected_urls, $item[ 'url' ] );
                }
            }

            if ( isset( $response[ 'malware_results' ][ 'scanned_at' ] ) )
            {
                update_option( 'sitelock_last_scanned_at', $response[ 'malware_results' ][ 'scanned_at' ] );
                update_option( 'sitelock_infected_urls', $infected_urls );
            }
        }
    }
    

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.9.0
     */
    public function enqueue_styles() {

        // fonts
        wp_enqueue_style( $this->plugin_name . '-googlefonts', '//fonts.googleapis.com/css?family=Oswald:400,300', array(), $this->version, 'all' );

        // general styles
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sitelock-admin.css', array(), $this->version, 'all' );

        // morris charts css
        wp_enqueue_style( $this->plugin_name . '-morris-charts', plugin_dir_url( __FILE__ ) . 'css/morris-0.5.1.css', array(), $this->version, 'all' );
    }
    

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.9.0
     */
    public function enqueue_scripts() {
        
        // raphael js
        wp_enqueue_script( $this->plugin_name . '-raphael-charts', plugin_dir_url( __FILE__ ) . 'js/raphael-2.1.0.min.js', array(), $this->version, false );
        
        // morris js
        wp_enqueue_script( $this->plugin_name . '-morris-charts', plugin_dir_url( __FILE__ ) . 'js/morris-0.5.1.min.js', array(), $this->version, false );

    }
    
    
    /**
     * Sanitize title with different separator
     *
     * @since 2.0.0
     * @param string $string    The string to be sanitized
     * @param string $separator The item to be used to separate words
     */
    public function sanitize_title( $string, $separator = '-' )
    {
        return str_replace( '-', $separator, sanitize_title( $string ) );
    }

}

