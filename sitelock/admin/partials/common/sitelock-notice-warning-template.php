<div>
    <p class="text-[14px]"><?php echo wp_kses_post($sitelock_warning_message); ?></p>
    <?php
    if (isset($sitelock_is_nginx) && $sitelock_is_nginx) {
        ?>
        <pre>
                            <?php
                            echo esc_html('
    # Disable directory listing
    autoindex off;

    # Block dangerous script files globally
    location ~* \.(phtml|phar|cgi|pl|py|asp|aspx|jsp)$ {
        deny all;
    }

    # Block dangerous script files inside wp-content/uploads
    location ~* ^/wp-content/uploads/.*\.(phtml|phar|cgi|pl|py|asp|aspx|jsp)$ {
        deny all;
    }

    # Block basic SQLi and XSS attempts in query strings
    if ($query_string ~* "(union(\s+all)?[\s+]+select|<script.*?>|%3Cscript%3E|%3C/script%3E)") {
        return 403;
    }
'); ?>
                    </pre>
        <p class="text-[14px]">Restart Nginx after making these changes.</p>
    <?php
    } ?>
</div>