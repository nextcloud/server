// config.php - Read-only configuration file
// All dynamic values are stored in database

return [
    // Static configuration - never modified by application
    'dbhost' => 'localhost',
    'dbname' => 'app_database',
    'dbuser' => 'app_user',
    'dbpassword' => 'secure_password',
    'dbtableprefix' => '',
    
    // Default values (overridden by database)
    'instanceid' => '',
    'version' => '0.0.0',
    'maintenance' => false,
    
    // Other static settings
    'trusted_domains' => [
        'localhost',
        'example.com',
    ],
    'datadirectory' => '/var/www/data',
    'overwrite.cli.url' => 'http://localhost',
    'htaccess.RewriteBase' => '/',
    'log_type' => 'file',
    'logfile' => '/var/www/data/owncloud.log',
    'loglevel' => 2,
    'installed' => false,
];
