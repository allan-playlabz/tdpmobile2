<?php
$domain = 'localhost';
return [
    'EmailTransport' => [
        'default' => [
            'url' => '',
        ],
    ],
    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => '',
        ],
    ],
    'Datasources' => [
        'default' => [
            'url' => '',
        ],
        'log_ds' => [
            'url' => '',
        ],
        'log_ds_ro' => [
            'url' => '',
        ],
    ],
    'Api' => [
        'url' => 'http://api.' . $domain,
        'secret' => '',
        'timeout' => 120,
    ],
    'ENVI' => 'DEV',
    'Default' => [
        'admin_email' => '',
        'from_email' => ['' => ''],
        'company_contact_no' => '',
        'contact_us_email' => '',
        'exception_emails' => [],
        'aws_prefix' => '',
    ],
    'TDP' => [
        'image_base' => '',
        'webroot' => '',
        'app_root' => '',
    ],
    'Features' => [
        'allotment' => true,
    ],
    'AWS' => [
        'credentials' => [
            'key'    => '',
            'secret' => '',
        ],
        'region' => '',
        'version' => '',
        'scheme' => '',
        'Bucket' => '',
        'prefix' => '',
    ],
    'AWSPublic' => [
        'credentials' => [
            'key'    => '',
            'secret' => '',
        ],
        'region' => '',
        'version' => '',
        'scheme' => '',
        'Bucket' => '',
        'public_host' => '',
    ],
    'Translation' => [
        'languages' => [
            'zh_CN' => '中文',
        ]
    ],
    'Login' => [
        'max_minutes' => 3,
        'max_count_per_max_minutes' => 5,
    ],
    'PdfGenerator' => [
        'url' => '',
        'api_key' => '',
    ],
    'Vouchers' => [
        'display_in_browser' => [
            'agent_ids' => [],
        ],
        'merged_expiration' => 60,
    ],
    // Set TWO_FACTOR_KEY in the environment (or encryptionKey here) before anyone sets up an authenticator.
    'TwoFactor' => [
        // 'encryptionKey' => '',
        // 'issuer' => 'TDP Agent',
    ],
    // Cloudflare Turnstile for "Try another way" on the authenticator code page (or the
    // TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY env). Local testing: site 1x00000000000000000000AA,
    // secret 1x0000000000000000000000000000000AA always pass (2x... always fail).
    'Turnstile' => [
        // 'siteKey' => '',
        // 'secretKey' => '',
    ],
    'Mailer' => [
        'api_url' => '',
        'secret' => '',
    ],
    'CreditLogs' => [
        'days_to_show' => 50,
    ],
    'AgentTopups' => [
        'months_to_show' => 6,
    ],
];
