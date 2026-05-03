<?php
// config.php - Read-only configuration file
// All dynamic settings moved to database

return [
    // Static configuration - never modified by application
    'dbhost' => 'localhost',
    'dbname' => 'myapp',
    'dbuser' => 'root',
    'dbpassword' => '',
    'dbtableprefix' => '',
    
    // Default values - overridden by database
    'instanceid' => '',
    'version' => '0.0.0',
    'maintenance' => false,
    
    // Other static settings
    'trusted_domains' => ['localhost'],
    'datadirectory' => '/var/www/data',
    'overwrite.cli.url' => 'http://localhost',
    'htaccess.RewriteBase' => '/',
    'log_type' => 'file',
    'logfile' => '/var/www/data/owncloud.log',
    'loglevel' => 2,
    'installed' => false,
];
