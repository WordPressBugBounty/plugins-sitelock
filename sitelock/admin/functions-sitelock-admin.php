<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * SiteLock SSO Link
 *
 * @since 1.9.0
 * @param string $page The dashboard page to view
 */
function sitelock_sso($page = '')
{
    $options = get_option('wpslp_options');
    $page    = $page != '' ? '&page=' . $page : '';

    return sitelock_api_url() . '/rlogin.php?token=' . (isset($options['auth_key']) ? $options['auth_key'] : '') . '&site_id=' . get_option('sitelock_site_id') . $page;
}

/**
 * WP Head stuff
 *
 * @since 1.9.0
 */
function sitelock_add_meta_tag()
{
    $rules = ['meta' => ['name' => [], 'content' => []]];
    echo wp_kses(get_option('sitelock_meta_tag'), $rules);
}

/**
 * Sitelock Badge Display
 *
 * @since 1.9.0
 */
function sitelock_add_this_script_footer()
{
    $location      = get_option('sitelock_badge_location');
    $badge_link    = get_option('sitelock_badge_link');
    $badge_img_src = get_option('sitelock_badge_img');

    // badge image is cached, but changing settings will change hash, requesting browsers to pull newest badge
    $badge_hash = hash('md5', get_option('sitelock_badge_color') . '-' . get_option('sitelock_badge_size') . '-' . get_option('sitelock_badge_type'));

    if ($location != 'hide' && $location != '0' && $badge_img_src && $badge_link) {
        $location = explode('-', $location);
        $loc1     = $location[0] == '0' ? 'top' : 'bottom';
        $loc1v    = '15px';
        $loc2     = $location[1] == '0' ? 'left' : 'right';
        $loc2v    = $location[1] == '50' ? '50%;margin-right:-56px' : '15px';
        include plugin_dir_path(__FILE__) . 'admin/partials/common/sitelock_footer_badge_script.php';
    }
}

/**
 * Decorate scan results
 *
 * @since 1.9.0
 * @param bool $results
 * @param bool $with_border
 */
function sitelock_decorate_scan_results($results, $with_border = false)
{
    if (!is_bool($results) && in_array(strtolower($results), ['red', 'green', 'yellow'])) {
        $background = strtolower($results);
    } else {
        $background = $results ? 'green' : 'red';
    }

    $background = $background == 'yellow' ? '#ecdb4f' : ($background == 'green' ? '#22bc26' : '#bc2622');
    $border     = $with_border ? 'border: 2px solid #fff; ' : '';

    return (!$results ? '<a href="' . sitelock_sso() . '" target="_blank">' : '') . ('<div style="' . $border . 'float: left; width: 15px; height: 15px; border-radius: 15px; background: ' . $background . '; margin: 0 10px 0 0; text-align: center; font-size: 10px; font-weight: bold; color: #fff; font-family: arial; line-height: 25px;' . (!$results ? ' cursor: pointer;' : '') . '"></div>') . (!$results ? '</a>' : '');
}

/**
 * Array to Options
 *
 * @since 1.9.0
 * @param array  $array
 * @param string $current
 */
function sitelock_array_to_options($array, $current = '')
{
    $html = '';

    foreach ($array as $key => $val) {
        $selected = $key == $current ? ' selected' : '';
        $html .= '<option value="' . $key . '"' . $selected . '>' . $val . '</option>';
    }

    return $html;
}

/**
 * Get page name from ID
 *
 * @since 1.9.0
 * @param int $page_id
 */
function sitelock_get_page_name($page_id)
{
    if (is_int($page_id) && $page_id > 0) {
        return get_page_link($page_id);
    }
}

/**
 * SiteLock API Url
 *
 * @since 1.9.0
 * @param string $location Defaults to 'secure'
 */
function sitelock_api_url($location = 'secure')
{
    if (($test_path = sitelock_get_test_var($location)) !== null) {
        return $test_path;
    }

    if ($location === 'mapi') {
        return 'https://mapi.sitelock.com/v3/connect';
    } elseif ($location === 'laravel') {
        return 'https://secure.sitelock.com/api/v1';
    } else {
        return 'https://' . $location . '.sitelock.com';
    }
}

/**
 * Helper function to simplify QA by using testing endpoint
 *
 * @since 5.0.0
 * @param string $var variable to swap out for testing
 */
function sitelock_get_test_var(string $var)
{
    $testing_file_path = plugin_dir_path(__FILE__) . '../testing.json';
    if (file_exists($testing_file_path)) {
        $content = file_get_contents($testing_file_path);
        if ($content) {
            $json = json_decode($content, true);
            if (isset($json[$var])) {
                return $json[$var];
            }
        }
    }
}

/**
 * Bytes to MB
 *
 * @since 1.9.0
 * @param int $bytes
 */
function sitelock_bytes_to_mb($bytes)
{
    $bytes = (int) $bytes / 1048576;

    return round($bytes, 2);
}

/**
 * Get site hostname
 *
 * @since 5.0.0
 * @return string
 */
function get_site_hostname($siteroot = false): string
{
    // return "domain" value from testing.json
    if (($test_domain = sitelock_get_test_var('domain')) !== null) {
        return $test_domain;
    }
    // return actual host value
    else {
        if ($siteroot) {
            return site_url();
        } else {
            $home_url = home_url();
            $parts    = wp_parse_url($home_url);

            return $parts['host'];
        }
    }
}

/**
 * Returns site id, if known, otherwise returns domain name (supports local)
 *
 * @since 5.0.0
 * @return string
 */
function get_site_identifier(): string
{
    $site_id = get_option('sitelock_site_id', '');
    if (!empty($site_id)) {
        return $site_id;
    } else {
        $hostname   = get_site_hostname();
        $parsed_url = wp_parse_url($hostname);

        return $parsed_url['host'] ?? $hostname;
    }
}

/**
 * Retrieves JWT claim by the name
 *
 * @since 5.0.0
 * @param  string $jwt   JWT token
 * @param  string $claim claim name
 * @return string claim value, if found
 */
function get_JWT_claim(string $jwt, string $claim)
{
    list($header, $payload, $signature) = explode('.', $jwt);
    $jsonToken                          = base64_decode($payload);
    $claims                             = json_decode($jsonToken, true);

    return $claims[$claim] ?? null;
}

/**
 * Encode data to a Base64 URL-safe string.
 *
 * Converts standard Base64 encoding into a URL-safe variant by:
 * - Replacing '+' with '-'
 * - Replacing '/' with '_'
 * - Trimming trailing '=' padding characters
 *
 * @param  string $data The input string or binary data to encode.
 * @return string The Base64 URL-safe encoded string.
 */
function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Country code to country name
 *
 * @since 1.9.0
 * @param string $code Two digit country code
 */
function sitelock_country_code_to_country($code)
{
    $country = '';
    $code    = strtoupper($code);
    if ($code == 'AF') {
        $country = 'Afghanistan';
    }
    if ($code == 'AX') {
        $country = 'Aland Islands';
    }
    if ($code == 'AL') {
        $country = 'Albania';
    }
    if ($code == 'DZ') {
        $country = 'Algeria';
    }
    if ($code == 'AS') {
        $country = 'American Samoa';
    }
    if ($code == 'AD') {
        $country = 'Andorra';
    }
    if ($code == 'AO') {
        $country = 'Angola';
    }
    if ($code == 'AI') {
        $country = 'Anguilla';
    }
    if ($code == 'AQ') {
        $country = 'Antarctica';
    }
    if ($code == 'AG') {
        $country = 'Antigua and Barbuda';
    }
    if ($code == 'AR') {
        $country = 'Argentina';
    }
    if ($code == 'AM') {
        $country = 'Armenia';
    }
    if ($code == 'AW') {
        $country = 'Aruba';
    }
    if ($code == 'AU') {
        $country = 'Australia';
    }
    if ($code == 'AT') {
        $country = 'Austria';
    }
    if ($code == 'AZ') {
        $country = 'Azerbaijan';
    }
    if ($code == 'BS') {
        $country = 'Bahamas the';
    }
    if ($code == 'BH') {
        $country = 'Bahrain';
    }
    if ($code == 'BD') {
        $country = 'Bangladesh';
    }
    if ($code == 'BB') {
        $country = 'Barbados';
    }
    if ($code == 'BY') {
        $country = 'Belarus';
    }
    if ($code == 'BE') {
        $country = 'Belgium';
    }
    if ($code == 'BZ') {
        $country = 'Belize';
    }
    if ($code == 'BJ') {
        $country = 'Benin';
    }
    if ($code == 'BM') {
        $country = 'Bermuda';
    }
    if ($code == 'BT') {
        $country = 'Bhutan';
    }
    if ($code == 'BO') {
        $country = 'Bolivia';
    }
    if ($code == 'BA') {
        $country = 'Bosnia and Herzegovina';
    }
    if ($code == 'BW') {
        $country = 'Botswana';
    }
    if ($code == 'BV') {
        $country = 'Bouvet Island (Bouvetoya)';
    }
    if ($code == 'BR') {
        $country = 'Brazil';
    }
    if ($code == 'IO') {
        $country = 'British Indian Ocean Territory (Chagos Archipelago)';
    }
    if ($code == 'VG') {
        $country = 'British Virgin Islands';
    }
    if ($code == 'BN') {
        $country = 'Brunei Darussalam';
    }
    if ($code == 'BG') {
        $country = 'Bulgaria';
    }
    if ($code == 'BF') {
        $country = 'Burkina Faso';
    }
    if ($code == 'BI') {
        $country = 'Burundi';
    }
    if ($code == 'KH') {
        $country = 'Cambodia';
    }
    if ($code == 'CM') {
        $country = 'Cameroon';
    }
    if ($code == 'CA') {
        $country = 'Canada';
    }
    if ($code == 'CV') {
        $country = 'Cape Verde';
    }
    if ($code == 'KY') {
        $country = 'Cayman Islands';
    }
    if ($code == 'CF') {
        $country = 'Central African Republic';
    }
    if ($code == 'TD') {
        $country = 'Chad';
    }
    if ($code == 'CL') {
        $country = 'Chile';
    }
    if ($code == 'CN') {
        $country = 'China';
    }
    if ($code == 'CX') {
        $country = 'Christmas Island';
    }
    if ($code == 'CC') {
        $country = 'Cocos (Keeling) Islands';
    }
    if ($code == 'CO') {
        $country = 'Colombia';
    }
    if ($code == 'KM') {
        $country = 'Comoros the';
    }
    if ($code == 'CD') {
        $country = 'Congo';
    }
    if ($code == 'CG') {
        $country = 'Congo the';
    }
    if ($code == 'CK') {
        $country = 'Cook Islands';
    }
    if ($code == 'CR') {
        $country = 'Costa Rica';
    }
    if ($code == 'CI') {
        $country = 'Cote d\'Ivoire';
    }
    if ($code == 'HR') {
        $country = 'Croatia';
    }
    if ($code == 'CU') {
        $country = 'Cuba';
    }
    if ($code == 'CY') {
        $country = 'Cyprus';
    }
    if ($code == 'CZ') {
        $country = 'Czech Republic';
    }
    if ($code == 'DK') {
        $country = 'Denmark';
    }
    if ($code == 'DJ') {
        $country = 'Djibouti';
    }
    if ($code == 'DM') {
        $country = 'Dominica';
    }
    if ($code == 'DO') {
        $country = 'Dominican Republic';
    }
    if ($code == 'EC') {
        $country = 'Ecuador';
    }
    if ($code == 'EG') {
        $country = 'Egypt';
    }
    if ($code == 'SV') {
        $country = 'El Salvador';
    }
    if ($code == 'GQ') {
        $country = 'Equatorial Guinea';
    }
    if ($code == 'ER') {
        $country = 'Eritrea';
    }
    if ($code == 'EE') {
        $country = 'Estonia';
    }
    if ($code == 'ET') {
        $country = 'Ethiopia';
    }
    if ($code == 'FO') {
        $country = 'Faroe Islands';
    }
    if ($code == 'FK') {
        $country = 'Falkland Islands (Malvinas)';
    }
    if ($code == 'FJ') {
        $country = 'Fiji the Fiji Islands';
    }
    if ($code == 'FI') {
        $country = 'Finland';
    }
    if ($code == 'FR') {
        $country = 'France, French Republic';
    }
    if ($code == 'GF') {
        $country = 'French Guiana';
    }
    if ($code == 'PF') {
        $country = 'French Polynesia';
    }
    if ($code == 'TF') {
        $country = 'French Southern Territories';
    }
    if ($code == 'GA') {
        $country = 'Gabon';
    }
    if ($code == 'GM') {
        $country = 'Gambia the';
    }
    if ($code == 'GE') {
        $country = 'Georgia';
    }
    if ($code == 'DE') {
        $country = 'Germany';
    }
    if ($code == 'GH') {
        $country = 'Ghana';
    }
    if ($code == 'GI') {
        $country = 'Gibraltar';
    }
    if ($code == 'GR') {
        $country = 'Greece';
    }
    if ($code == 'GL') {
        $country = 'Greenland';
    }
    if ($code == 'GD') {
        $country = 'Grenada';
    }
    if ($code == 'GP') {
        $country = 'Guadeloupe';
    }
    if ($code == 'GU') {
        $country = 'Guam';
    }
    if ($code == 'GT') {
        $country = 'Guatemala';
    }
    if ($code == 'GG') {
        $country = 'Guernsey';
    }
    if ($code == 'GN') {
        $country = 'Guinea';
    }
    if ($code == 'GW') {
        $country = 'Guinea-Bissau';
    }
    if ($code == 'GY') {
        $country = 'Guyana';
    }
    if ($code == 'HT') {
        $country = 'Haiti';
    }
    if ($code == 'HM') {
        $country = 'Heard Island and McDonald Islands';
    }
    if ($code == 'VA') {
        $country = 'Holy See (Vatican City State)';
    }
    if ($code == 'HN') {
        $country = 'Honduras';
    }
    if ($code == 'HK') {
        $country = 'Hong Kong';
    }
    if ($code == 'HU') {
        $country = 'Hungary';
    }
    if ($code == 'IS') {
        $country = 'Iceland';
    }
    if ($code == 'IN') {
        $country = 'India';
    }
    if ($code == 'ID') {
        $country = 'Indonesia';
    }
    if ($code == 'IR') {
        $country = 'Iran';
    }
    if ($code == 'IQ') {
        $country = 'Iraq';
    }
    if ($code == 'IE') {
        $country = 'Ireland';
    }
    if ($code == 'IM') {
        $country = 'Isle of Man';
    }
    if ($code == 'IL') {
        $country = 'Israel';
    }
    if ($code == 'IT') {
        $country = 'Italy';
    }
    if ($code == 'JM') {
        $country = 'Jamaica';
    }
    if ($code == 'JP') {
        $country = 'Japan';
    }
    if ($code == 'JE') {
        $country = 'Jersey';
    }
    if ($code == 'JO') {
        $country = 'Jordan';
    }
    if ($code == 'KZ') {
        $country = 'Kazakhstan';
    }
    if ($code == 'KE') {
        $country = 'Kenya';
    }
    if ($code == 'KI') {
        $country = 'Kiribati';
    }
    if ($code == 'KP') {
        $country = 'Korea';
    }
    if ($code == 'KR') {
        $country = 'Korea';
    }
    if ($code == 'KW') {
        $country = 'Kuwait';
    }
    if ($code == 'KG') {
        $country = 'Kyrgyz Republic';
    }
    if ($code == 'LA') {
        $country = 'Lao';
    }
    if ($code == 'LV') {
        $country = 'Latvia';
    }
    if ($code == 'LB') {
        $country = 'Lebanon';
    }
    if ($code == 'LS') {
        $country = 'Lesotho';
    }
    if ($code == 'LR') {
        $country = 'Liberia';
    }
    if ($code == 'LY') {
        $country = 'Libyan Arab Jamahiriya';
    }
    if ($code == 'LI') {
        $country = 'Liechtenstein';
    }
    if ($code == 'LT') {
        $country = 'Lithuania';
    }
    if ($code == 'LU') {
        $country = 'Luxembourg';
    }
    if ($code == 'MO') {
        $country = 'Macao';
    }
    if ($code == 'MK') {
        $country = 'Macedonia';
    }
    if ($code == 'MG') {
        $country = 'Madagascar';
    }
    if ($code == 'MW') {
        $country = 'Malawi';
    }
    if ($code == 'MY') {
        $country = 'Malaysia';
    }
    if ($code == 'MV') {
        $country = 'Maldives';
    }
    if ($code == 'ML') {
        $country = 'Mali';
    }
    if ($code == 'MT') {
        $country = 'Malta';
    }
    if ($code == 'MH') {
        $country = 'Marshall Islands';
    }
    if ($code == 'MQ') {
        $country = 'Martinique';
    }
    if ($code == 'MR') {
        $country = 'Mauritania';
    }
    if ($code == 'MU') {
        $country = 'Mauritius';
    }
    if ($code == 'YT') {
        $country = 'Mayotte';
    }
    if ($code == 'MX') {
        $country = 'Mexico';
    }
    if ($code == 'FM') {
        $country = 'Micronesia';
    }
    if ($code == 'MD') {
        $country = 'Moldova';
    }
    if ($code == 'MC') {
        $country = 'Monaco';
    }
    if ($code == 'MN') {
        $country = 'Mongolia';
    }
    if ($code == 'ME') {
        $country = 'Montenegro';
    }
    if ($code == 'MS') {
        $country = 'Montserrat';
    }
    if ($code == 'MA') {
        $country = 'Morocco';
    }
    if ($code == 'MZ') {
        $country = 'Mozambique';
    }
    if ($code == 'MM') {
        $country = 'Myanmar';
    }
    if ($code == 'NA') {
        $country = 'Namibia';
    }
    if ($code == 'NR') {
        $country = 'Nauru';
    }
    if ($code == 'NP') {
        $country = 'Nepal';
    }
    if ($code == 'AN') {
        $country = 'Netherlands Antilles';
    }
    if ($code == 'NL') {
        $country = 'Netherlands the';
    }
    if ($code == 'NC') {
        $country = 'New Caledonia';
    }
    if ($code == 'NZ') {
        $country = 'New Zealand';
    }
    if ($code == 'NI') {
        $country = 'Nicaragua';
    }
    if ($code == 'NE') {
        $country = 'Niger';
    }
    if ($code == 'NG') {
        $country = 'Nigeria';
    }
    if ($code == 'NU') {
        $country = 'Niue';
    }
    if ($code == 'NF') {
        $country = 'Norfolk Island';
    }
    if ($code == 'MP') {
        $country = 'Northern Mariana Islands';
    }
    if ($code == 'NO') {
        $country = 'Norway';
    }
    if ($code == 'OM') {
        $country = 'Oman';
    }
    if ($code == 'PK') {
        $country = 'Pakistan';
    }
    if ($code == 'PW') {
        $country = 'Palau';
    }
    if ($code == 'PS') {
        $country = 'Palestinian Territory';
    }
    if ($code == 'PA') {
        $country = 'Panama';
    }
    if ($code == 'PG') {
        $country = 'Papua New Guinea';
    }
    if ($code == 'PY') {
        $country = 'Paraguay';
    }
    if ($code == 'PE') {
        $country = 'Peru';
    }
    if ($code == 'PH') {
        $country = 'Philippines';
    }
    if ($code == 'PN') {
        $country = 'Pitcairn Islands';
    }
    if ($code == 'PL') {
        $country = 'Poland';
    }
    if ($code == 'PT') {
        $country = 'Portugal, Portuguese Republic';
    }
    if ($code == 'PR') {
        $country = 'Puerto Rico';
    }
    if ($code == 'QA') {
        $country = 'Qatar';
    }
    if ($code == 'RE') {
        $country = 'Reunion';
    }
    if ($code == 'RO') {
        $country = 'Romania';
    }
    if ($code == 'RU') {
        $country = 'Russian Federation';
    }
    if ($code == 'RW') {
        $country = 'Rwanda';
    }
    if ($code == 'BL') {
        $country = 'Saint Barthelemy';
    }
    if ($code == 'SH') {
        $country = 'Saint Helena';
    }
    if ($code == 'KN') {
        $country = 'Saint Kitts and Nevis';
    }
    if ($code == 'LC') {
        $country = 'Saint Lucia';
    }
    if ($code == 'MF') {
        $country = 'Saint Martin';
    }
    if ($code == 'PM') {
        $country = 'Saint Pierre and Miquelon';
    }
    if ($code == 'VC') {
        $country = 'Saint Vincent and the Grenadines';
    }
    if ($code == 'WS') {
        $country = 'Samoa';
    }
    if ($code == 'SM') {
        $country = 'San Marino';
    }
    if ($code == 'ST') {
        $country = 'Sao Tome and Principe';
    }
    if ($code == 'SA') {
        $country = 'Saudi Arabia';
    }
    if ($code == 'SN') {
        $country = 'Senegal';
    }
    if ($code == 'RS') {
        $country = 'Serbia';
    }
    if ($code == 'SC') {
        $country = 'Seychelles';
    }
    if ($code == 'SL') {
        $country = 'Sierra Leone';
    }
    if ($code == 'SG') {
        $country = 'Singapore';
    }
    if ($code == 'SK') {
        $country = 'Slovakia (Slovak Republic)';
    }
    if ($code == 'SI') {
        $country = 'Slovenia';
    }
    if ($code == 'SB') {
        $country = 'Solomon Islands';
    }
    if ($code == 'SO') {
        $country = 'Somalia, Somali Republic';
    }
    if ($code == 'ZA') {
        $country = 'South Africa';
    }
    if ($code == 'GS') {
        $country = 'South Georgia and the South Sandwich Islands';
    }
    if ($code == 'ES') {
        $country = 'Spain';
    }
    if ($code == 'LK') {
        $country = 'Sri Lanka';
    }
    if ($code == 'SD') {
        $country = 'Sudan';
    }
    if ($code == 'SR') {
        $country = 'Suriname';
    }
    if ($code == 'SJ') {
        $country = 'Svalbard & Jan Mayen Islands';
    }
    if ($code == 'SZ') {
        $country = 'Swaziland';
    }
    if ($code == 'SE') {
        $country = 'Sweden';
    }
    if ($code == 'CH') {
        $country = 'Switzerland, Swiss Confederation';
    }
    if ($code == 'SY') {
        $country = 'Syrian Arab Republic';
    }
    if ($code == 'TW') {
        $country = 'Taiwan';
    }
    if ($code == 'TJ') {
        $country = 'Tajikistan';
    }
    if ($code == 'TZ') {
        $country = 'Tanzania';
    }
    if ($code == 'TH') {
        $country = 'Thailand';
    }
    if ($code == 'TL') {
        $country = 'Timor-Leste';
    }
    if ($code == 'TG') {
        $country = 'Togo';
    }
    if ($code == 'TK') {
        $country = 'Tokelau';
    }
    if ($code == 'TO') {
        $country = 'Tonga';
    }
    if ($code == 'TT') {
        $country = 'Trinidad and Tobago';
    }
    if ($code == 'TN') {
        $country = 'Tunisia';
    }
    if ($code == 'TR') {
        $country = 'Turkey';
    }
    if ($code == 'TM') {
        $country = 'Turkmenistan';
    }
    if ($code == 'TC') {
        $country = 'Turks and Caicos Islands';
    }
    if ($code == 'TV') {
        $country = 'Tuvalu';
    }
    if ($code == 'UG') {
        $country = 'Uganda';
    }
    if ($code == 'UA') {
        $country = 'Ukraine';
    }
    if ($code == 'AE') {
        $country = 'United Arab Emirates';
    }
    if ($code == 'GB') {
        $country = 'United Kingdom';
    }
    if ($code == 'US') {
        $country = 'United States of America';
    }
    if ($code == 'UM') {
        $country = 'United States Minor Outlying Islands';
    }
    if ($code == 'VI') {
        $country = 'United States Virgin Islands';
    }
    if ($code == 'UY') {
        $country = 'Uruguay, Eastern Republic of';
    }
    if ($code == 'UZ') {
        $country = 'Uzbekistan';
    }
    if ($code == 'VU') {
        $country = 'Vanuatu';
    }
    if ($code == 'VE') {
        $country = 'Venezuela';
    }
    if ($code == 'VN') {
        $country = 'Vietnam';
    }
    if ($code == 'WF') {
        $country = 'Wallis and Futuna';
    }
    if ($code == 'EH') {
        $country = 'Western Sahara';
    }
    if ($code == 'YE') {
        $country = 'Yemen';
    }
    if ($code == 'ZM') {
        $country = 'Zambia';
    }
    if ($code == 'ZW') {
        $country = 'Zimbabwe';
    }
    if ($country == '') {
        $country = $code;
    }

    return $country;
}

function sitelock_get_directory_tree($dir, $depth = 0, $max_depth = 2)
{
    if ($depth > $max_depth) {
        return [];
    }

    $result = [];
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            $result[$item] = sitelock_get_directory_tree($fullPath, $depth + 1, $max_depth);
        }
    }

    return $result;
}

/**
 * Get list of existing WordPress directories for limiting file execution
 *
 * @return array
 */
function sitelock_get_existing_blockable_directories()
{
    $base_path = ABSPATH;

    // Predefined list of potentially blockable directories (relative to ABSPATH)
    $directories_to_check = [
        'wp-content/uploads/',
        'wp-content/cache/',
        'wp-content/backups/',
        'wp-content/upgrade/',
        'wp-content/temp/',
        'wp-content/tmp/',
        'wp-content/languages/',
        'wp-content/ai1wm-backups/',
        'wp-content/wflogs/',
        'wp-content/et-cache/',
    ];

    $existing_directories = [];

    foreach ($directories_to_check as $relative_path) {
        $full_path = trailingslashit($base_path) . $relative_path;
        if (is_dir($full_path)) {
            $existing_directories[] = $relative_path;
        }
    }

    return $existing_directories;
}

/**
 * Get Sitelock Redirect URL based on type
 *
 * @param  string $type The type of redirect. Valid values: 'comparePlan', 'signup', 'whatIsThis'.
 * @return string
 */
function get_sitelock_redirect_url($type = ''): string
{
    switch ($type) {
        case 'comparePlan':
            return 'https://www.sitelock.com/pricing';
        case 'signup':
            return 'https://www.sitelock.com/wordpress-signup/?ref=';
        case 'whatIsThis':
            return 'https://www.sitelock.com/help-center';
        default:
            return 'https://www.sitelock.com/wordpress-signup';
    }
}
