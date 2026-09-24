<?php

return [
    'admin' => [
        'brand_name' => env('ADMIN_BRAND_NAME', 'AGC IKENEGBU'),
        'brand_subtitle' => env('ADMIN_BRAND_SUBTITLE', 'Church Management System'),
        'page_title_suffix' => env('ADMIN_PAGE_TITLE_SUFFIX', 'AGC IKENEGBU'),
        'login_title' => env('ADMIN_LOGIN_TITLE', 'Administrator Portal'),
        'login_primary_label' => env('ADMIN_LOGIN_PRIMARY_LABEL', 'AGC IKENEGBU'),
        'login_primary_subtitle' => env('ADMIN_LOGIN_PRIMARY_SUBTITLE', 'Church Management Platform'),
        'favicon_path' => env('ADMIN_FAVICON_PATH', 'images/ag-logo.jpeg'),
        'primary_logo_video_path' => env('ADMIN_PRIMARY_LOGO_VIDEO_PATH', 'videos/Create_a_cinematic_D_animatio.mp4'),
        'search_placeholder' => env('ADMIN_SEARCH_PLACEHOLDER', 'Search members, events, pages...'),
    ],

    'public' => [
        'author' => env('PUBLIC_SITE_AUTHOR', 'Assemblies of God Church-Ikenegbu'),
        'site_name' => env('PUBLIC_SITE_NAME', 'Assemblies of God Church-Ikenegbu'),
        'short_name' => env('PUBLIC_SITE_SHORT_NAME', 'AGC Ikenegbu'),
        'tagline' => env('PUBLIC_SITE_TAGLINE', 'Assemblies of God Church Owerri'),
        'default_title' => env('PUBLIC_SITE_DEFAULT_TITLE', 'Assemblies of God Church-Ikenegbu | Assemblies of God Church Owerri — Worship & Community'),
        'default_description' => env('PUBLIC_SITE_DEFAULT_DESCRIPTION', 'Assemblies of God Church-Ikenegbu Assemblies of God in Owerri, Nigeria — spirit-filled worship, Bible teaching, family ministries, and community outreach. Join us Sundays 8:00 AM & 10:30 AM.'),
        'default_og_title' => env('PUBLIC_SITE_DEFAULT_OG_TITLE', 'Assemblies of God Church-Ikenegbu | Assemblies of God Church Owerri'),
        'default_og_description' => env('PUBLIC_SITE_DEFAULT_OG_DESCRIPTION', 'Spirit-filled worship, Bible teaching, and community outreach in Owerri, Nigeria.'),
        'logo_path' => env('PUBLIC_SITE_LOGO_PATH', 'images/ag-logo.jpeg'),
        'logo_video_path' => env('PUBLIC_SITE_LOGO_VIDEO_PATH', 'videos/Create_a_cinematic_D_animatio.mp4'),
        'welcome_video_path' => env('PUBLIC_SITE_WELCOME_VIDEO_PATH', 'videos/Create_a_cinematic_D_animatio.mp4'),
        'welcome_video_label' => env('PUBLIC_SITE_WELCOME_VIDEO_LABEL', 'AGC Ikenegbu welcome video'),
        'adsense_client_id' => env('ADSENSE_CLIENT_ID', ''),
        'adsense_enabled' => filter_var(env('ADSENSE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID', ''),
    ],

    'email' => [
        'from_name' => env('BRAND_EMAIL_FROM_NAME', 'Assemblies of God Church Ikenegbu'),
        'from_email' => env('BRAND_EMAIL_FROM_EMAIL', 'info@agikenebgu.org'),
        'church_address' => env('BRAND_EMAIL_CHURCH_ADDRESS', '11 Archdeacon, Dennis Street, Ikenegbu, Owerri, Imo State'),
        'church_phone' => env('BRAND_EMAIL_CHURCH_PHONE', '08034095171'),
        'church_website' => env('BRAND_EMAIL_CHURCH_WEBSITE', env('APP_URL', 'http://localhost')),
        'pastor_name' => env('BRAND_EMAIL_PASTOR_NAME', 'Pastor Emmanuel'),
    ],
];
