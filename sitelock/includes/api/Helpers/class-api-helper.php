<?php

class ApiHelper
{
    /**
     * API url
     *
     * @since    1.9.0
     * @access   public
     * @var string $API    Defines the external API base url
     */
    public $API;
    public $version;
    protected $auth;

    public function __construct($version, $auth = null)
    {
        $this->version = $version;
        $this->auth    = $auth;
        $this->API     = sitelock_api_url('laravel');
    }

    public function call_api($url, $method = 'GET', $payload = [])
    {
        // submit api call
        if ($method == 'POST') {
            // post
            $response = wp_remote_post(
                $url,
                [
                    'method' => $method,
                    'body'   => $payload,
                ]
            );
        } else {
            // get
            $response = wp_remote_get(
                $url
            );
        }

        // response code
        $response_code = wp_remote_retrieve_response_code($response);

        // response body
        $response_body = wp_remote_retrieve_body($response);

        // check for successful response
        if ($response_code == '200') {
            // convert response to object
            $json_response = json_decode($response_body, true);

            return $json_response;
        } else {
            $error_response = json_decode($response_body, true);
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();

                return $error_message;
            } else {
                return $error_response;
            }
        }
    }
    public function setAuthManager($authManager)
    {
        $this->auth = $authManager;
    }
    /**
     * Calls external API
     *
     * @since   5.0.0
     * @param string $action  LAPI endpoint
     * @param string $method  'GET' or 'POST'
     * @param array  $params  array of post data will be converted to json
     * @param bool   $use_key used to determine if a key is required
     */
    public function call_laravel_api($action, $method = 'GET', $params = null, $use_key = true)
    {
        $args = [
            'sslverify' => true, // SSL verification enabled for secure production requests
            'timeout'   => 15,
        ];

        $key = ($use_key ? $this->auth->get_auth_key() : null);

        if ($key) {
            $args['headers']['Authorization'] = 'Bearer ' . $key;
        }

        if ($method == 'POST') {
            $response = wp_remote_post(
                $this->API . '/' . $action,
                $args
            );
        } else {
            $response = wp_remote_get(
                $this->API . '/' . $action,
                $args
            );
        }

        // response code
        $response_code = wp_remote_retrieve_response_code($response);

        // response body
        $response_body = wp_remote_retrieve_body($response);

        // check for successful response
        if ($response_code == '200') {
            // convert response to object
            $json_response = json_decode($response_body, true);

            return $json_response['data'];
        } else {
            $json_error_response                    = json_decode($response_body, true);
            $json_error_response['error']['status'] = 'error';

            return $json_error_response['error'];
        }
    }
}
