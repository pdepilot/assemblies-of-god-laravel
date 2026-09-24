/* Navigation & Mock Data */
window.CMS_CONFIG = {
    brand: {
        name: 'AGC IKENEGBU',
        subtitle: 'Church Management System',
        agVideo: '../videos/3D_video.mp4'
    },

    nav: [
        { id: 'dashboard', label: 'Dashboard', icon: 'fa-gauge-high', href: 'admin-dashboard' },
        {
            id: 'ag', label: 'AGC IKENEGBU', type: 'group', children: [
                { id: 'members', label: 'Members', icon: 'fa-users', href: 'members' },
                { id: 'ministry-settings', label: 'Ministry Settings', icon: 'fa-sliders', href: 'ministry-settings' },
                { id: 'children', label: 'Children Ministry', icon: 'fa-child', href: 'children' },
                { id: 'sunday-school', label: 'Sunday School', icon: 'fa-book-open', href: 'sunday-school' },
                { id: 'teens', label: 'Teen Ministry', icon: 'fa-user-graduate', href: 'teens' },
                { id: 'youths', label: 'Youth Ministry', icon: 'fa-people-group', href: 'youths' },
                { id: 'men', label: "Men's Ministry", icon: 'fa-person', href: 'men' },
                { id: 'women', label: "Women's Ministry", icon: 'fa-person-dress', href: 'women' },
                { id: 'widows', label: 'Widows', icon: 'fa-person-dress', href: 'widows' },
                { id: 'music', label: 'Music', icon: 'fa-music', href: 'music' },
                { id: 'choir', label: 'Choir', icon: 'fa-users-line', href: 'choir' },
                { id: 'ushers', label: 'Ushering', icon: 'fa-door-open', href: 'ushers' },
                { id: 'media', label: 'Media Team', icon: 'fa-video', href: 'media' },
                { id: 'visitors', label: 'Visitors', icon: 'fa-handshake', href: 'visitors' },
                { id: 'attendance', label: 'Attendance', icon: 'fa-clipboard-check', href: 'attendance' },
                { id: 'events', label: 'Events', icon: 'fa-calendar-days', href: 'events' },
                { id: 'sermons', label: 'Sermons & Live', icon: 'fa-book-bible', href: 'sermon' },
                { id: 'donations', label: 'Donations', icon: 'fa-hand-holding-heart', href: 'donations' },
                { id: 'recurring-donations', label: 'Recurring Giving', icon: 'fa-rotate', href: 'recurring-donations' },
                { id: 'partnerships', label: 'Kingdom Partnership', icon: 'fa-handshake-angle', href: 'partnerships' },
                { id: 'stewardship', label: 'Stewardship', icon: 'fa-chart-line', href: 'stewardship' },
                { id: 'contact', label: 'Contact Inbox', icon: 'fa-envelope-open-text', href: 'contact' },
                { id: 'newsletter-subscribers', label: 'Newsletter Subscribers', icon: 'fa-at', href: 'newsletter-subscribers' },
                { id: 'ag-testimonies', label: 'Testimonies', icon: 'fa-quote-left', href: 'testimonies' },
                { id: 'reports', label: 'Reports', icon: 'fa-chart-pie', href: 'analytics/reports' },
                { id: 'messages', label: 'Send Emails', icon: 'fa-envelope', href: 'messages' }
            ]
        },
        {
            id: 'communication-hub', label: 'COMMUNICATION HUB', type: 'group', children: [
                { id: 'ch-dashboard', label: 'Communication Dashboard', icon: 'fa-gauge-high', href: 'communication-hub/dashboard' },
                { id: 'ch-birthdays', label: 'Birthday Calendar', icon: 'fa-cake-candles', href: 'communication-hub/birthdays' },
                { id: 'ch-email', label: 'Email Center', icon: 'fa-envelope-open-text', href: 'communication-hub/email-center' },
                { id: 'ch-sms', label: 'SMS Center', icon: 'fa-comment-sms', href: 'communication-hub/sms-center' },
                { id: 'ch-templates', label: 'Templates', icon: 'fa-file-lines', href: 'communication-hub/templates' },
                { id: 'ch-automation', label: 'Automation Rules', icon: 'fa-robot', href: 'communication-hub/automation' },
                { id: 'ch-newsletter', label: 'Newsletter Builder', icon: 'fa-newspaper', href: 'communication-hub/newsletter' },
                { id: 'ch-campaigns', label: 'Campaign Manager', icon: 'fa-bullhorn', href: 'communication-hub/campaigns' },
                { id: 'ch-scheduled', label: 'Scheduled Messages', icon: 'fa-clock', href: 'communication-hub/scheduled' },
                { id: 'ch-queue', label: 'Communication Queue', icon: 'fa-list-check', href: 'communication-hub/queue' },
                { id: 'ch-recipients', label: 'Recipient Groups', icon: 'fa-user-group', href: 'communication-hub/recipients' },
                { id: 'ch-logs', label: 'Communication Logs', icon: 'fa-clipboard-list', href: 'communication-hub/logs' },
                { id: 'ch-analytics', label: 'Analytics', icon: 'fa-chart-line', href: 'communication-hub/analytics' },
                { id: 'ch-notifications', label: 'Notification Center', icon: 'fa-bell', href: 'communication-hub/notifications' },
                { id: 'ch-ai', label: 'AI Message Assistant', icon: 'fa-wand-magic-sparkles', href: 'communication-hub/ai-assistant' }
            ]
        },
        {
            id: 'financial-erp', label: 'FINANCIAL ERP', type: 'group', children: [
                { id: 'erp-launch', label: 'Open Financial ERP', icon: 'fa-chart-line', href: 'financial-erp/launch' } // opens erp/login
            ]
        },
        {
            id: 'registration-portals', label: 'REGISTRATION PORTALS', type: 'group', children: [
                { id: 'rp-dashboard', label: 'Dashboard', icon: 'fa-gauge-high', href: 'registration-portals/dashboard' },
                { id: 'rp-create', label: 'Create Portal', icon: 'fa-plus-circle', href: 'registration-portals/create' },
                { id: 'rp-portals', label: 'All Portals', icon: 'fa-door-open', href: 'registration-portals/index' },
                { id: 'rp-templates', label: 'Templates', icon: 'fa-layer-group', href: 'registration-portals/templates' },
                { id: 'rp-registrants', label: 'Registrants', icon: 'fa-users', href: 'registration-portals/registrants' },
                { id: 'rp-certificates', label: 'Certificates', icon: 'fa-certificate', href: 'registration-portals/certificates' },
                { id: 'rp-reports', label: 'Reports', icon: 'fa-chart-column', href: 'registration-portals/reports' }
            ]
        },
        {
            id: 'website', label: 'WEBSITE MANAGEMENT', type: 'group', children: [
                { id: 'pages', label: 'Pages', icon: 'fa-file-lines', href: 'website/pages' },
                { id: 'homepage-activities', label: 'Homepage Activities', icon: 'fa-hands', href: 'website/pages#agActivitiesEditor' },
                { id: 'blog', label: 'Blog', icon: 'fa-newspaper', href: 'website/blog' },
                { id: 'about-content', label: 'About Content', icon: 'fa-church', href: 'website/about-content' },
                { id: 'worship-schedule', label: 'Our Worship', icon: 'fa-calendar-days', href: 'website/worship/edit' },
                { id: 'team-section', label: 'Team Sections', icon: 'fa-people-group', href: 'admin-team-section' },
                { id: 'media-library', label: 'Media Library', icon: 'fa-folder-open', href: 'website/media-library' },
                { id: 'seo', label: 'SEO Manager', icon: 'fa-magnifying-glass-chart', href: 'website/seo' },
                { id: 'site-traffic', label: 'Site Traffic', icon: 'fa-chart-area', href: 'website/site-traffic' }
            ]
        },
        {
            id: 'system', label: 'SYSTEM', type: 'group', children: [
                { id: 'settings', label: 'Settings', icon: 'fa-gear', href: 'settings' },
                { id: 'security-dashboard', label: 'Security', icon: 'fa-shield-halved', href: 'system/security-dashboard' },
                { id: 'activity-logs', label: 'Activity Logs', icon: 'fa-clock-rotate-left', href: 'system/activity-logs' }
            ]
        }
    ],

    notifications: [
        { icon: 'blue', title: 'Attendance Submitted', text: 'Youth Department - 142 present', time: '18 min ago' },
        { icon: 'green', title: 'Donation Received', text: 'N50,000 - Building Fund', time: '1 hr ago' },
        { icon: 'gold', title: 'SEO Audit Complete', text: 'Score improved to 87/100', time: '3 hrs ago' }
    ],

    members: [
        { id: 'AGCI-00001', name: 'Pastor Emmanuel Nwosu', email: 'emmanuel@agikenebgu.org', dept: 'Leadership', status: 'active', joined: '2018-03-12' },
        { id: 'AGCI-00002', name: 'Grace Okonkwo', email: 'grace.o@email.com', dept: 'Choir', status: 'active', joined: '2019-07-22' },
        { id: 'AGCI-00003', name: 'David Eze', email: 'david.eze@email.com', dept: 'Youth', status: 'active', joined: '2020-01-15' },
        { id: 'AGCI-00004', name: 'Blessing Adeyemi', email: 'blessing.a@email.com', dept: 'Ushering', status: 'inactive', joined: '2021-05-08' },
        { id: 'AGCI-00005', name: 'Chioma Ibe', email: 'chioma.ibe@email.com', dept: 'Children', status: 'active', joined: '2022-09-30' },
        { id: 'AGCI-00006', name: 'Samuel Uche', email: 'samuel.u@email.com', dept: 'Media', status: 'active', joined: '2023-02-14' }
    ]
};
