<?php

class SitesManager
{
    public $apiHelper;
    public $auth;

    public function __construct($apiHelper, $auth = null)
    {
        $this->auth      = $auth;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Send a refresh signal for the API
     *
     * @since      2.0.0
     */
    public function refresh_api()
    {
        update_option('sitelock_refresh_api', 'true');
    }

    /**
     * Get Domain Validation
     *
     * @since    1.9.0
     * @param int    $site_id
     * @param string $scan_type 'app_scan', 'sql_injection', 'site_scan', 'smart_scan', 'port_scan', 'ssl_check', 'spam_check', 'dast_scan'
     */
    public function post_queue_scan($site_id, $scan_type)
    {
        $this->refresh_api();

        $params = [
            'site_id'   => $site_id,
            'scan_type' => $scan_type,
        ];

        return $this->apiHelper->call_api('scan_now', 'POST', $params);
    }

    /**
     * Requesting all site features
     *
     * @since   5.0.0
     */
    public function get_features()
    {
        $site_id = get_option('sitelock_site_id');
        $url     = $site_id.'/features/';

        return $this->apiHelper->call_laravel_api($url);
    }

    /**
     * Requesting all sites
     *
     * @since   5.0.0
     */
    public function get_sites()
    {
        $site_id = get_option('sitelock_site_id');
        $url     = $site_id.'/sites';

        return $this->apiHelper->call_laravel_api($url);
    }

    /**
    * Requesting all partner preferences
    *
    * @since   5.0.2
    */
    public function get_partner_preference($pref = '')
    {
        $site_id = get_option('sitelock_site_id');
        $url     = $site_id.'/partner-preferences/'.$pref;

        return $this->apiHelper->call_laravel_api($url);
    }

    /**
     * Requesting trigger scan now api
     *
     * @since   5.0.2
     */
    public function post_scan_now($scanType)
    {
        $site_id = get_option('sitelock_site_id');
        $url     = $site_id . '/features/' . $scanType . '/scan';

        return $this->apiHelper->call_laravel_api($url, 'POST');
    }
}
