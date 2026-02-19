<?php

require_once plugin_dir_path(__FILE__) . 'Helpers/class-api-helper.php';
require_once plugin_dir_path(__FILE__) . 'Helpers/class-sitelock-verification-service.php';
require_once plugin_dir_path(__FILE__) . 'class-auth-manager.php';
require_once plugin_dir_path(__FILE__) . 'class-sites-manager.php';

class Sitelock_API
{
    public $auth;
    public $sites;
    private $apiHelper;

    public $verification_service;

    public function __construct($version)
    {
        $this->apiHelper            = new ApiHelper($version);
        $this->auth                 = new AuthManager($version, $this->apiHelper);
        $this->sites                = new SitesManager($this->apiHelper, $this->auth);
        $this->verification_service = new SiteLock_Verification_Service($version);
        $this->apiHelper->setAuthManager($this->auth);
    }

    /**
     * Update Page Protect
     *
     * @since    2.0.0
     * @param int    $site_id
     * @param string $page_url url to be used for page protect
     * @param string $status   on or off
     */
    public function update_page_protect($site_id = false, $page_url = false, $status = false)
    {
        if ($site_id && $page_url) {
            $action = $status == 'on' || $status == 'add' ? 'add' : 'del';

            if (isset($page_url[ 0 ]) && $page_url[ 0 ] != '/') {
                $page_url = '/' . $page_url;
            }

            $params = [
                'site_id' => $site_id,
                'pattern' => 'equals',
                'url'     => $page_url,
            ];

            $this->sites->refresh_api();

            $response = $this->apiHelper->call_api("lp_{$action}_url", 'POST', $params);

            return $response;
        }
    }

    /**
     * Update Badge Settings
     *
     * @since    1.9.0
     * @param int    $site_id    site id
     * @param string $badge_type shield options
     * @param bool   $display_ci if true render contact info when shield is clicked
     */
    public function update_badge_settings($site_id, $badge_type)
    {
        if ($site_id && $badge_type) {
            $params = [
                'site_id'      => $site_id,
                'type'         => $badge_type,
                'contact_info' => false, // this option is no longer in Dashboard and defaults to not displaying contact info
            ];

            # sitelock_debug( $params, 'x' );

            return $this->apiHelper->call_api('badge', 'POST', $params);
        }
    }

    /**
     * Global banner message
     *
     * @since   3.1.2
     * @param string $action save or empty
     * @param string $value  if action is save then get value from here
     */
    public function banner($action = '', $value = '')
    {
        $option_field = 'sitelock_banner_msg';

        if ($action == 'save') {
            update_option($option_field, $value);
        } elseif ($action == '') {
            // get option value
            $response = get_option($option_field);

            // now delete it
            delete_option($option_field);
        }

        return $response;
    }
}
