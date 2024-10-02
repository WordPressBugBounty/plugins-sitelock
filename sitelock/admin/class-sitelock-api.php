<?php

/**
 * All api calls to external API
 *
 * @package    Sitelock
 * @subpackage Sitelock/admin
 * @author     Todd Low <tlow@sitelock.com>
 */
class Sitelock_API {

    /**
     * API url
     *
     * @since    1.9.0
     * @access   public
     * @var      string    $API    Defines the external API base url
     */
    public $API;
    

    /**
     * The version of this plugin.
     *
     * @since    3.1.2
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    

    /**
     * Auth Key Tag
     *
     * @since    1.9.0
     * @access   public
     * @var      string    $wpslp_tag    Tag used for storing and retrieving data from db
     */
    public $wpslp_tag;
    

    /**
     * Auth Key and SAML Key
     *
     * @since    1.9.0
     * @access   public
     * @var      array    $wpslp_options    Stores values of auth key
     */
    public $wpslp_options;
    
    
    /**
     * String to time value for setting the token expiration date
     *
     * @since   2.0.0
     * @access  public
     */
    public $when_to_expire_token;
    
    
    /**
     * Manually refresh all API calls (boolean)
     *
     * @since  2.0.0
     * @access public
     */
    public $refresh_api;
    

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.9.0
     * @param    string    $version        The version of this plugin.
     */
    public function __construct( $version ) 
    {
        // Sets the version
        $this->version        = $version;

        // API url
        $this->API            = sitelock_api_url( 'mapi' );

        // option tag 
        $this->wpslp_tag      = 'wpslp_options';

        // option values
        $this->wpslp_options  = array(
            'auth_key' => false,
            'saml_key' => false,
            'validated' => false
        );

        if ( get_option( 'sitelock_refresh_api' ) == 'true' || isset( $_POST[ 'refresh_scan_results' ] ) )
        {
            $this->refresh_api = true;
            $this->clear_refresh_api();
        }
        else
        {
            $this->refresh_api = false;
        }
        
        $this->when_to_expire_token = '+10 minutes';
        
        // attempt to get auth key
        $this->set_auth_key();
    }
    

    /**
     * Get Sites Call
     *
     * @since    1.9.0
     */
    public function get_sites_call() {
        
        // check for expired token or refresh signal
        if ( !$this->has_token_expired() )
        {
            $response = get_option( 'sitelock_account_sites' );
        }
        
        // check that we data to return, if not default to API
        if ( empty( $response ) )
        {
            $response = $this->call_api( 'sites' );
            
            // save response in db as local copy to avoid excessive API calls    
            update_option( 'sitelock_account_sites', $response );
        }
        
        return $response;
    }
    

    /**
     * Get Scan Info Call
     *
     * @since    1.9.0
     * @param    int        $site_id
     */
    public function get_scan_info_call( $site_id ) 
    {
        if ( $site_id ) 
        {    
            // check for expired token or refresh signal
            if ( !$this->has_token_expired() )
            {
                $response = get_option( 'sitelock_account_scaninfo' );
            }
            
            // check that we data to return, if not default to API
            if ( empty( $response ) )
            {
                $params = array( 
                    'site_id' => $site_id 
                );

                $response = $this->call_api( 'scaninfo', 'POST', $params );
                
                // save response in db as local copy to avoid excessive API calls    
                update_option( 'sitelock_account_scaninfo', $response );
            }
            
            return $response;
        }
    }
    
    
    /**
     * Send a refresh signal for the API
     *
     * @since      2.0.0
     */
    public function refresh_api()
    {
        update_option( 'sitelock_refresh_api', 'true' );
    }
    
    
    /**
     * Remove refresh signal for the API
     *
     * @since      2.0.0
     */
    public function clear_refresh_api()
    {
        update_option( 'sitelock_refresh_api', '' );
    }
    

    /**
     * Get Domain Validation 
     *
     * @since    1.9.0
     * @param    int        $site_id
     * @param    string     $scan_type 'app_scan', 'sql_injection', 'site_scan', 'smart_scan', 'port_scan', 'ssl_check', 'spam_check', 'dast_scan'
     */
    public function post_queue_scan( $site_id, $scan_type ) 
    {
        $this->refresh_api();

        $params = array(
            'site_id' => $site_id, 
            'scan_type' => $scan_type
        );

        return $this->call_api( 'scan_now', 'POST', $params );
    }
    

    /**
     * Get WAF General Information 
     *
     * @since    2.0.0
     * @param    int        $site_id
     */
    public function get_waf_general_info( $site_id ) 
    {
        $response = $this->call_api( 'waf_info', 'POST', array( 'site_id' => $site_id ) );

        if ( isset( $response[ 'site_status' ][0] ) && is_array( $response[ 'site_status' ][0] ) )
        {
            $response[ 'site_status' ] = (string) $response[ 'site_status' ][0]; 
        }

        return $response;
    }
    

    /**
     * Get WAF General Information 
     *
     * @since    2.0.0
     * @param    int        $site_id
     * @param    string     $bypass
     * @param    string     $use_true_shield
     * @param    string     $use_true_speed
     */
    public function set_waf_general_settings( $site_id, $bypass, $use_true_shield, $use_true_speed ) 
    {    
        $params = array(
            'site_id' => $site_id, 
            'bypass' => $bypass, 
            'use_true_shield' => $use_true_shield, 
            'use_true_speed' => $use_true_speed
        );
        
        $this->refresh_api();
        
        return $this->call_api( 'waf_settings', 'POST', $params );
    }
    

    /**
     * Get WAF General Information 
     *
     * @since    2.0.0
     * @param    int        $site_id
     * @param    string     $site_ip
     */
    public function set_site_ip( $site_id, $site_ip ) 
    {
        $params = array(
            'site_id' => $site_id,
            'site_ip' => $site_ip
        );
        
        $this->refresh_api();
        
        return $this->call_api( 'waf_site_origin', 'POST', $params );
    }
    

    /**
     * Set Purge Cache
     *
     * @since    2.0.0
     * @param    int        $site_id
     * @param    string     $purge_pattern Optional
     */
    public function set_purge_cache( $site_id, $purge_pattern = '' ) 
    {
        $params = array(
            'site_id' => $site_id, 
            'purge_pattern' => $purge_pattern
        );
        
        $this->refresh_api();
        
        return $this->call_api( 'waf_purge', 'POST', $params );
    }
    
    
    /**
     * Build full CDN payload
     *
     * @since 2.0.0
     * @param   int     $site_id    Site ID
     * @param   array   $array      Array of data to be converted to XML string
     */
    public function build_cdn_payload( $site_id, $array )
    {
        $params = array();
        
        foreach ( $array as $key => $val )
        {
            $params[ $key ] = $val;
        }

        $this->refresh_api();
        
        return $this->set_cdn_info( $site_id, $params );
    }
    
    
    /**
     * Get TXT Records
     *
     * @since   2.0.0
     * @param   int     $site_id    Site ID
     * 
     * @removed 3.4.0
     * 
        public function get_txt_records( $site_id )
        {
            return $this->call_api( 'waf_domain_txt', 'POST', array( 'site_id' => $site_id ) );
        }
     *
     */

    
    
    /**
     * Set CDN Info
     *
     * @since   2.0.0
     * @param   int     $site_id    Site ID
     * @param   array   $params     Array 
     */
    public function set_cdn_info( $site_id, $params )
    {
        $params[ 'site_id' ] = $site_id;

        return $this->call_api( 'cdn_set', 'POST', $params );
    }
    

    /**
     * WAF Domain Validation
     *
     * @since    2.0.0
     * @param    int        $site_id
     * 
     * @removed  3.4.0
     * 
        public function waf_validate_domain( $site_id ) 
        {
            return $this->call_api( 'waf_domain_validation', 'POST', array( 'site_id' => $site_id ) );
        }
     * 
     */
    

    /**
     * Get WAF CDN Information 
     *
     * @since    2.0.0
     * @param    int        $site_id
     */
    public function get_waf_cdn_info( $site_id ) 
    {
        $response = $this->call_api( 'cdn_info', 'POST', array( 'site_id' => $site_id ) );

        if ( isset( $response[ 'status' ][0] ) )
        {
            $response[ 'status' ] = (string) $response[ 'status' ][0];
        }

        return $response;
    }
    

    /**
     * SMART Get Settings
     *
     * @since    2.0.0
     * @param    int        $site_id
     */
    public function get_smart_settings( $site_id ) 
    {
        $params = array(
            'site_id' => $site_id, 
            'get' => true
        );

        $response = $this->call_api( 'smart_settings', 'POST', $params );

        return $response;
    }
    

    /**
     * SMART Set Settings
     *
     * @since    2.0.0
     * @param    int        $site_id
     * @param    array      $settings
     */
    public function set_smart_settings( $site_id, $settings ) 
    {
        $params = array();
        $params[ 'site_id' ] = $site_id;
        $parrams[ 'get' ] = false;
        
        foreach ( $settings as $key => $val )
        {
            $params[ $key ] = $val;
        }
        
        $this->refresh_api();
        
        return $this->call_api( 'smart_settings', 'POST', $params );
    }
    

    /**
     * Get Domain Validation 
     *
     * @since    1.9.0
     * @param    int        $site_id
     * @param    boolean    $set_ready
     */
    public function get_domain_validate_call( $site_id, $set_ready = null ) 
    {
        $params = array();
        $params[ 'site_id' ] = $site_id;

        if ( $set_ready ) 
        {
            $params[ 'verify' ] = true;   
        }

        return $this->call_api( 'validate_domain', 'POST', $params );
    }
    

    /**
     * Get Malware Scan Results
     *
     * @since    1.9.0
     * @param    int        $site_id     
     */
    public function get_malware_scan_results( $site_id ) 
    {
        if ( $site_id ) 
        {    
            // check for expired token or refresh signal
            if ( !$this->has_token_expired() )
            {
                $response = get_option( 'sitelock_malware_get_scan' );
            }
            
            // check that we have data to return, if not default to API
            if ( empty( $response ) )
            {
                $params = array( 
                    'site_id' => $site_id
                );

                $response = $this->call_api( 'malware_quick', 'POST', $params );
                
                // save response in db as local copy to avoid excessive API calls    
                update_option( 'sitelock_malware_get_scan', $response );
            }
            
            $response = array();
            $response[ 'malware_results' ][ 'items'      ] = isset( $response[ 'pages' ] ) ? $response[ 'pages' ] : array();
            $response[ 'malware_results' ][ 'scanned_at' ] = date( 'Y-m-d' ); // @todo find out last scan date
            
            return $response;
        }
    }
    
    
    /**
     * Update Page Protect
     *
     * @since    2.0.0
     * @param    int        $site_id    
     * @param    string     $page_url   url to be used for page protect  
     * @param    string     $status     on or off
     */
    public function update_page_protect( $site_id = false, $page_url = false, $status = false ) 
    {
        if ( $site_id && $page_url ) 
        {    
            $action = $status == 'on' || $status == 'add' ? 'add' : 'del';
            
            if ( isset( $page_url[ 0 ] ) && $page_url[ 0 ] != '/' )
            {
                $page_url = '/' . $page_url;
            }
            
            $params = array( 
                'site_id' => $site_id, 
                'pattern' => 'equals', 
                'url' => $page_url 
            );

            $this->refresh_api();
            
            $response = $this->call_api( "lp_{$action}_url", 'POST', $params );
            
            return $response;
        }
    }
    
    
    /**
     * Initialize SMART single scan
     *
     * @since   1.9.0
     * @param   int         $site_id
     * @param   int         $total_size
     */
    public function post_smart_single_scan_init( $site_id, $total_size ) 
    {
        if ( $site_id && $total_size ) 
        {
            $params = array();
            $params[ 'site_id'    ] = $site_id;
            $params[ 'total_size' ] = $total_size;
            $params[ 'ciphers'    ] = array();
            
            if ( function_exists( 'openssl_get_cipher_methods' ) )
            {
                $params[ 'ciphers' ]      = openssl_get_cipher_methods( true );
                $params[ 'feature_code' ] = 'smart';
                $params[ 'service' ]      = 'OpenSSL';
            }
            
            $this->refresh_api();
            return $this->call_api( 's3_init', 'POST', $params );
        }
    }
    
    
    /**
     * Queue single smart scan
     *
     * @since   1.9.0
     * @param   int         $site_id
     * @param   int         $queue_id
     * @param   string      $url
     */
    public function post_smart_single_scan_queue( $site_id, $queue_id, $url ) 
    {
        if ( $site_id && $queue_id && $url ) 
        {
            $params = array(
                'site_id' => $site_id, 
                'queue_id' => $queue_id,
                'url' => $url
            );

            $this->refresh_api();

            return $this->call_api( 's3_queue', 'POST', $params );
        }
    }
    

    /**
     * Check WAF SSL 
     *
     * @since   2.0.0
     * @param   int     $site_id
     * 
     * @removed 3.4.0
     * 
        public function check_waf_ssl( $site_id = false ) 
        {
            if ( $site_id ) 
            {
                return $this->call_api( 'waf_ssl', 'POST', array( 'site_id' => $site_id ) );
            }
        }
     * 
     */
    

    /**
     * Get single smart scan status
     *
     * @since 1.9.0
     * @param   int     $site_id
     * @param   int     $queue_id
     */
    public function get_smart_single_scan_status( $queue_id, $site_id ) 
    {
        if ( $queue_id && $site_id ) 
        {
            $params = array( 
                'queue_id' => $queue_id, 
                'site_id' => $site_id
            );

            return $this->call_api( 's3_status', 'POST', $params );
        }
    }
    
    
    /**
     * Update Badge Settings
     *
     * @since    1.9.0
     * @param    int        $site_id        site id
     * @param    string     $badge_type     shield options
     * @param    boolean    $display_ci     if true render contact info when shield is clicked     
     */
    public function update_badge_settings( $site_id, $badge_type ) 
    {
        if ( $site_id && $badge_type ) 
        {
            $params = array(
                'site_id'       => $site_id, 
                'type'          => $badge_type, 
                'contact_info'  => false // this option is no longer in Dashboard and defaults to not displaying contact info
            );

            # sitelock_debug( $params, 'x' );

            return $this->call_api( 'badge', 'POST', $params );
        }
    }
    

    /**
     * Attempts to retrieve auth key
     *
     * @since    1.9.0
     */
    public function set_auth_key() 
    {
        if ( get_option( $this->wpslp_tag ) ) 
        {
            $this->wpslp_options = get_option( $this->wpslp_tag );

            #if ( $this->get_auth_key() && !$this->is_auth_key_valid() ) {
                // auth key is no longer valid, force logout to clear data
                #header( 'Location: ' . ( admin_url() . 'tools.php?page=sitelock&logout=true' ) );
                #exit;
            #}
        } 
        else 
        {
            update_option( $this->wpslp_tag, $this->wpslp_options );
        }
    }
    
    
    /** 
     * Check if the token cache has expired and we need to reset it
     * 
     * @since 2.1.0
     * @access public
     */
    public function check_to_reset_cache()
    {
        $values = get_option( $this->wpslp_tag );
        $time   = time();
        
        if ( ( isset( $values[ 'validated' ] ) && $values[ 'validated' ] < $time ) || $this->refresh_api )
        {
            // force the api to refresh and get new validated date
            $values[ 'validated' ] = strtotime( $this->when_to_expire_token );
            update_option( $this->wpslp_tag, $values );
        }
    }
    
    
    /**
     * Has the token expired - responds boolean true (yes) false (no)
     *
     * @since   2.0.0
     * @access  public
     */
    public function has_token_expired()
    {
        if ( $this->refresh_api )
        {
            return true;
        }
        
        $values = get_option( $this->wpslp_tag );
        $time   = time();
        
        if ( ( isset( $values[ 'validated' ] ) && $values[ 'validated' ] > $time ) )
        {
            return false; # yes
        }
        
        return true; # no 
    }
    
    
    /** 
     * Get WordQuick
     * 
     * @since 2.2.0
     * @param string $domain
     */
    public function get_word_quick( $domain )
    { 
        // check for expired token or refresh signal
        if ( !$this->has_token_expired() )
        {
            $response = get_option( 'sitelock_word_quick' );
        } 
        
        // check that we data to return, if not default to API
        if ( empty( $response ) )
        {
            // site id can be a domain name in this case only
            $params = array(
                'site_id' => str_replace( array( 'http://', 'https://' ), array( '', '' ), $domain )
            );

            // call api
            $response = $this->call_api( 'wordquick', 'POST', $params );

            // save response in db as local copy to avoid excessive API calls    
            update_option( 
                'sitelock_word_quick', 
                $response 
            );

            // check for smart options
            $can_config = $can_fix = 0;

            if ( isset( $response[ 'products' ][ 'items' ][ 'smart' ] ) )
            {
                // user has smart so by default they can config and fix
                $can_config = $can_fix = 1;

                // check for sub feature which can override default values of $can_config and $can_fix
                if ( isset( $response[ 'products' ][ 'items' ][ 'smart' ][ 'can_cfg' ] ) )
                {
                    // set config / fix values
                    $can_config = !empty( $response[ 'products' ][ 'items' ][ 'smart' ][ 'can_cfg' ] ) ? 1 : 0;
                    $can_fix    = !empty( $response[ 'products' ][ 'items' ][ 'smart' ][ 'can_fix' ] ) ? 1 : 0;
                }
            }

            // save in db
            update_option( 
                'sitelock_smart_config_fix', 
                array( 
                    'config' => $can_config, 
                    'fix' => $can_fix 
                ) 
            );
        }

        return $response;
    }
    

    /**
     * Gets auth key from wpslp_options
     *
     * @since    1.9.0
     */
    public function get_auth_key() 
    {    
        return isset( $this->wpslp_options[ 'auth_key' ] ) ? $this->wpslp_options[ 'auth_key' ] : false;
    }
    
             
    /**
     * Get Secret
     * 
     * @since   2.1.1
     */
    public function get_secret()
    {
        $secret = get_option( 'sl_secret' );
        
        if ( empty( $secret ) )
        {
            $secret = base64_encode( substr( hash( 'SHA256', microtime() ), 4, 56 ) );
        }
        
        update_option( 'sl_secret', $secret );
        
        return $secret;
    }


    /**
     * Sets auth key when received as request
     *
     * @since    1.9.0
     */
    public function handle_auth() 
    {
        // get auth key
        $auth_key = wp_strip_all_tags( $_REQUEST['auth_key'] );
        
        if ( $auth_key ) 
        {
            // prepare params
            $params = array(
                'enc_token' => $auth_key, 
                'secret' => $this->get_secret()
            );
            
            // decrypt auth key
            $get_auth = $this->call_api( 'decrypt', 'POST', $params, false );

            // clear the secret
            update_option( 'sl_secret', '' );
            
            // open plugin
            header( 'Location: ' . admin_url() . 'tools.php?page=sitelock' );
            exit;
        }
    }

    
    /** 
     * Save new token
     * 
     * @since   3.1.2
     * @param   object  $object     New token object
     */
    private function save_new_token( $token = '' )
    {
        if ( $token != '' )
        {
            // prepare auth key array
            $this->wpslp_options[ 'auth_key'  ] = preg_replace( "/[^ \w]+/", "", $token );
            $this->wpslp_options[ 'validated' ] = strtotime( $this->when_to_expire_token );
            
            // save auth key
            update_option( $this->wpslp_tag, $this->wpslp_options );
        }

        return true;
    }


    /** 
     * Global banner message
     * 
     * @since   3.1.2
     * @param   string  $action     save or empty
     * @param   string  $value      if action is save then get value from here
     */
    public function banner( $action = '', $value = '' )
    {
        $option_field = 'sitelock_banner_msg';

        if ( $action == 'save' )
        {
            update_option( $option_field, $value );
        }
        else if ( $action == '' )
        {
            // get option value
            $response = get_option( $option_field );

            // now delete it
            delete_option( $option_field );
        }
        
        return $response;
    }


    /** 
     * Generate unique ID
     * 
     * @since 3.1.2
     */
    private function unique_id() 
    {
        $data = function_exists('random_bytes')
            ? random_bytes(16) : ( function_exists('openssl_random_pseudo_bytes')
            ? openssl_random_pseudo_bytes(16) : ''
        );

        if ( !empty($data) ) {
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000, // version number 4
            mt_rand( 0, 0x3fff ) | 0x8000, // 2 MSBs hold zero and one for variant DCE1.1
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    

    /**
     * Calls external API
     *
     * @since   1.9.0
     * @param   string  $action     Action of API to use
     * @param   string  $method     'GET' or 'POST'
     * @param   array   $params     array of post data will be converted to json
     * @param   boolean $use_key    used to determin if a key is required
     */
    private function call_api( $action, $method = 'GET', $params = null, $use_key = true ) 
    {    
        $test_all = false;
        $test_action = 'waf_info_';

        $key = ( $use_key ? $this->get_auth_key() : null );
    
        if ( ( $use_key && $key ) || !$use_key ) 
        {
            // build payload
            $payload = array(
                "pluginVersion" => $this->version,
                "apiTargetVersion" => "3.0.1",
                "token" => $key,
                "requests" => array(
                    "id" => $this->unique_id(),
                    "action" => $action,
                    "params" => $params
                )
            );

            // if ( $action == $test_action || $test_all )
            // {
            //     sitelock_debug( $this->API, 'hr' );
            //     sitelock_debug( $payload, 'hr' );
            // }

            // base64 payload and json encode
            $payload = json_encode( $payload );

            // if ( $action == $test_action || $test_all )
            // {
            //     sitelock_debug( $payload, 'hr' );
            // }

            $payload = base64_encode( $payload );

            // i?
            
            // submit api call
            if ( $method == 'POST' ) 
            {
                // post
                $response = wp_remote_post( 
                    $this->API, 
                    array(
                        'method'        => $method,
                        #'headers'       => array(
                        #    'content-type'      => 'text/json'
                        #),
                        'user-agent'    => 'SiteLock WP Plugin',
                        'body'          => $payload,
                        'sslverify'     => false
                    ) 
                );
            } 
            else 
            {
                // get
                $response = wp_remote_get( 
                    $this->API, 
                    array( 
                        'sslverify' => false 
                    ) 
                );
            }

            // response code
            $response_code = wp_remote_retrieve_response_code( $response );

            // response body
            $response_body = wp_remote_retrieve_body( $response );

            // if ( $action == $test_action || $test_all )
            // {
            //     sitelock_debug( $response_body, 'x' );
            // }

            // check for successful response
            if ( $response_code == '200' )
            {
                // convert response to object
                $json_response = json_decode( $response_body, true );

                // global banner
                if ( !empty( $json_response[ 'banner' ] ) )
                {
                    // save banner
                    $this->banner( 'save', $json_response[ 'banner' ] );
                }

                // force logout
                if ( !empty( $json_response[ 'forceLogout' ] ) )
                {
                    // send user to login page for rate exceeded notification
                    $logout = admin_url() . 'tools.php?page=sitelock&logout=true&expired=true';
                    ?>
                    <p><em>Loading...</em></p>
                    <script>
                        window.open( "<?php echo esc_url_raw( $logout ); ?>", "_self" );
                    </script>
                    <?php
                    
                    exit;
                }

                // token
                if ( !empty( $json_response[ 'newToken' ] ) )
                {
                    // update the token
                    $this->save_new_token( $json_response[ 'newToken' ] );
                }

                return $json_response[ 'responses' ][ 0 ][ 'data' ];
            }
            else
            {
                if ( is_wp_error( $response ) )
                {
                    $error_message = $response->get_error_message();
                    return false;
                }
                else if ( $usage_exceeded ) 
                {  
                    // send user to login page for rate exceeded notification
                    $logout = admin_url() . 'tools.php?page=sitelock&logout=true&exceeded=true';
                    ?>
                    <p><em>Loading...</em></p>
                    <script>
                        window.open( "<?php echo esc_url_raw( $logout ); ?>", "_self" );
                    </script>
                    <?php
                    exit;
                }
            }   
        } 
        else 
        {
            // render error message? log out for now, forces reset of auth key
            if ( !isset( $_GET[ 'logout' ] ) )
            {
                header( 'Location: ' . admin_url() . 'tools.php?page=sitelock&logout=true' );
                exit;
            }
        }
    }

}

