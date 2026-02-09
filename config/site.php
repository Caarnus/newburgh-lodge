<?php

return [
    'newsletter_label' => env('NEWSLETTER_LABEL', 'Newsletter'),
    'newsletter_route' => env('NEWSLETTER_ROUTE', 'newsletters'),
    'org_name' => env('ORG_NAME', 'Organization Name'),
    'contact_form_to' => env('CONTACT_FORM_TO', 'organization@example.com'),
    'contact_form_name' => env('CONTACT_FORM_NAME', 'Organization Contact'),
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_name' => env('ADMIN_NAME', 'Administrator'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'admin_reset_password_on_seed' => env('ADMIN_RESET_PASSWORD_ON_SEED', false),
    'new_user_notify_enabled' => env('NEW_USER_NOTIFY_ENABLED', true),
    'new_user_notify_emails' => array_values(array_filter(array_map('trim',explode(',', env('NEW_USER_NOTIFY_EMAILS', ''))))),
];
