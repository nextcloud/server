<?php
// config.php - Read-only configuration file
// All dynamic values moved to database

return [
    // Static configuration - never modified by application
    'dbhost' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'nextcloud',
    'dbuser' => getenv('DB_USER') ?: 'nextcloud',
    'dbpassword' => getenv('DB_PASSWORD') ?: '',
    'dbtableprefix' => 'oc_',
    
    // Default values - overridden by database
    'instanceid' => '',
    'maintenance' => false,
    'version' => '',
    
    // Other static settings
    'trusted_domains' => [
        getenv('TRUSTED_DOMAIN') ?: 'localhost',
    ],
    'datadirectory' => getenv('DATA_DIR') ?: '/var/www/html/data',
    'overwrite.cli.url' => getenv('OVERWRITE_CLI_URL') ?: 'http://localhost',
    'htaccess.RewriteBase' => '/',
];
