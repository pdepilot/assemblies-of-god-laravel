<?php

return [
    'admin' => [
        'brand_name' => env('ADMIN_BRAND_NAME', 'AGC IKENEGBU'),
        'brand_subtitle' => env('ADMIN_BRAND_SUBTITLE', 'Church Management System'),
        'page_title_suffix' => env('ADMIN_PAGE_TITLE_SUFFIX', 'AGC IKENEGBU CMS'),
        'login_title' => env('ADMIN_LOGIN_TITLE', 'Administrator Portal'),
        'login_primary_label' => env('ADMIN_LOGIN_PRIMARY_LABEL', 'AGC IKENEGBU'),
        'login_primary_subtitle' => env('ADMIN_LOGIN_PRIMARY_SUBTITLE', 'Church Management Platform'),
        'login_secondary_label' => env('ADMIN_LOGIN_SECONDARY_LABEL', 'Send Down Thy Glory'),
        'login_secondary_subtitle' => env('ADMIN_LOGIN_SECONDARY_SUBTITLE', 'Event Management Platform'),
        'favicon_path' => env('ADMIN_FAVICON_PATH', 'images/ag-logo.jpeg'),
        'primary_logo_video_path' => env('ADMIN_PRIMARY_LOGO_VIDEO_PATH', 'videos/Create_a_cinematic_D_animatio.mp4'),
        'secondary_logo_video_path' => env('ADMIN_SECONDARY_LOGO_VIDEO_PATH', 'sdgt/videos/3d-logo.mp4'),
    ],

    'public' => [
        'author' => env('PUBLIC_SITE_AUTHOR', 'AG Ikenebgu Assemblies of God'),
        'site_name' => env('PUBLIC_SITE_NAME', 'AG Ikenebgu Assemblies of God'),
        'short_name' => env('PUBLIC_SITE_SHORT_NAME', 'AG Ikenebgu'),
        'tagline' => env('PUBLIC_SITE_TAGLINE', 'Assemblies of God Church Owerri'),
        'default_title' => env('PUBLIC_SITE_DEFAULT_TITLE', 'AG Ikenebgu | Assemblies of God Church Owerri — Worship & Community'),
        'default_description' => env('PUBLIC_SITE_DEFAULT_DESCRIPTION', 'AG Ikenebgu Assemblies of God in Owerri, Nigeria — spirit-filled worship, Bible teaching, family ministries, and community outreach. Join us Sundays 8:00 AM & 10:30 AM.'),
        'default_og_title' => env('PUBLIC_SITE_DEFAULT_OG_TITLE', 'AG Ikenebgu | Assemblies of God Church Owerri'),
        'default_og_description' => env('PUBLIC_SITE_DEFAULT_OG_DESCRIPTION', 'Spirit-filled worship, Bible teaching, and community outreach in Owerri, Nigeria.'),
        'logo_path' => env('PUBLIC_SITE_LOGO_PATH', 'images/ag-logo.jpeg'),
        'logo_video_path' => env('PUBLIC_SITE_LOGO_VIDEO_PATH', 'videos/Create_a_cinematic_D_animatio.mp4'),
        'welcome_video_path' => env('PUBLIC_SITE_WELCOME_VIDEO_PATH', 'videos/Create_a_cinematic_D_animatio.mp4'),
        'welcome_video_label' => env('PUBLIC_SITE_WELCOME_VIDEO_LABEL', 'AG Ikenebgu welcome video'),
        'sdtg_label' => env('PUBLIC_SITE_SDTG_LABEL', 'Send Down Thy Glory'),
    ],

    'email' => [
        'from_name' => env('BRAND_EMAIL_FROM_NAME', 'Assemblies of God Church Ikenegbu'),
        'from_email' => env('BRAND_EMAIL_FROM_EMAIL', 'info@agikenebgu.org'),
        'church_address' => env('BRAND_EMAIL_CHURCH_ADDRESS', '11 Archdeacon, Dennis Street, Ikenegbu, Owerri, Imo State'),
        'church_phone' => env('BRAND_EMAIL_CHURCH_PHONE', '+234 800 000 0000'),
        'church_website' => env('BRAND_EMAIL_CHURCH_WEBSITE', env('APP_URL', 'http://localhost')),
        'pastor_name' => env('BRAND_EMAIL_PASTOR_NAME', 'Pastor Emmanuel'),
    ],
];
