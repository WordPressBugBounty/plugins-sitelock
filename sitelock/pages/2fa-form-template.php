<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <title>Two-Factor Authentication</title>
</head>
<body>
    <form id="sitelock-2fa-post" method="post" action="<?php echo esc_url($action_url); ?>">
        <input type="hidden" name="sitelock_2fa_nonce" value="<?php echo esc_attr($nonce); ?>">
        <noscript>
            <p>JavaScript is required to complete login. Please enable JS.</p>
            <button type="submit">Continue</button>
        </noscript>
    </form>
    <script>
        (function(){
            var f = document.getElementById('sitelock-2fa-post');
            if (f) {
                f.submit();
            } else {
                location.href = "<?php echo esc_js(wp_login_url()); ?>";
            }
        })();
    </script>
</body>
</html>