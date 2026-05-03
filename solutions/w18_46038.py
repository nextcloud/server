<?php
// config.php - Read-only configuration file
// All dynamic values moved to database

return [
    // Static configuration - never modified by application
    'dbhost' => 'localhost',
    'dbname' => 'myapp',
    'dbuser' => 'user',
    'dbpassword' => 'password',
    'dbtableprefix' => '',
    
    // Instance ID - stored in database, generated on first install
    // 'instanceid' => '', // REMOVED - now in database
    
    // Maintenance mode - stored in database, defaults to false
    // 'maintenance' => false, // REMOVED - now in database
    
    // Version - stored in database, updated during upgrades
    // 'version' => '', // REMOVED - now in database
    
    // Other static settings
    'trusted_domains' => [
        'localhost',
        'example.com',
    ],
    'datadirectory' => '/var/www/data',
    'overwrite.cli.url' => 'http://localhost',
    'htaccess.RewriteBase' => '/',
    'log_type' => 'file',
    'logfile' => '/var/www/nextcloud.log',
    'logdateformat' => 'Y-m-d H:i:s',
    'installed' => false,
];
