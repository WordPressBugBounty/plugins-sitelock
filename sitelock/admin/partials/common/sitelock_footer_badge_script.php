<?php defined( 'ABSPATH' ) || exit; ?>
<script>
    var html = '<div id="sitelock_shield_logo" class="fixed_btm" style="<?php echo esc_attr($loc1); ?>:<?php echo esc_attr($loc1v); ?>;position:fixed;_position:absolute;min-width:100px;min-height:50px;<?php echo esc_attr($loc2); ?>:<?php echo esc_attr($loc2v); ?>;">';
    html += '<a href="<?php echo esc_url_raw($badge_link); ?>" onclick="window.open(\'<?php echo esc_url_raw($badge_link); ?>\',\'SiteLock\',\'width=600,height=600,left=160,top=170\');return false;">';
    html += '<img alt="malware removal and website security" title="SiteLock" src="<?php echo esc_url_raw($badge_img_src) . '?' . esc_attr($badge_hash) ?>" />';
    html += '</a>';
    html += '</div>';

    if (document.body != null) {
        window.onload = function () {
            let g = document.createElement('div');
            g.setAttribute('id', 'sitelock_shield_logo_placeholder');
            document.body.appendChild(g);
            document.getElementById('sitelock_shield_logo_placeholder').innerHTML = html;
        }
    }
</script>