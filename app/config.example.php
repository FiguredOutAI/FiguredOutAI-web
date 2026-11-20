<?php
/**
 * FiguredOutAI site configuration.
 * Edit these values before uploading to Hostinger.
 */
return [
    // Public URL of the site, no trailing slash. Must match your live domain.
    'base_url'      => 'https://figuredoutai.com',
    'site_name'     => 'FiguredOutAI',
    'tagline'       => 'AI-native infrastructure for modern business',
    'timezone'      => 'Asia/Kolkata',

    // Where contact-form enquiries are emailed. Also stored in Admin → Leads.
    'contact_email' => 'hello@figuredoutai.com',
    // "From" address for outgoing mail. Use a mailbox that exists on your Hostinger domain.
    'mail_from'     => 'hello@figuredoutai.com',


    // IndexNow key (Bing, Yandex, Seznam, Naver). A matching <key>.txt file sits in the site root.
    'indexnow_key'  => '1b6a662ad711e519bfae8b3c78fb6384',

    // Token for the optional Hostinger cron URL: /app/cron.php is blocked, use /cron.php?token=...
    'cron_token'    => 'CHANGE-ME-random-string',

    'posts_per_page' => 9,
    'default_author' => 'FiguredOutAI Team',
];
