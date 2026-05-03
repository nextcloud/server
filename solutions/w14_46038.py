<?php
// config.php - Read-only configuration file
// All dynamic values moved to database

return [
    // Static configuration - never modified by application
    'dbhost' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'nextcloud',
    'dbuser' => getenv('DB_USER') ?: 'nextcloud',
    'dbpassword' => getenv('DB_PASSWORD') ?: '',
    'dbprefix' => getenv('DB_PREFIX') ?: 'oc_',
    'dbtype' => getenv('DB_TYPE') ?: 'mysql',
    'dbtableprefix' => getenv('DB_TABLE_PREFIX') ?: '',
    
    // Instance ID - now in database (appconfig table)
    // 'instanceid' => 'removed',
    
    // Version - now in database (appconfig table)  
    // 'version' => 'removed',
    
    // Maintenance mode - now in database (appconfig table)
    // 'maintenance' => false,
    
    // Other static settings
    'trusted_domains' => explode(',', getenv('TRUSTED_DOMAINS') ?: 'localhost'),
    'datadirectory' => getenv('DATA_DIRECTORY') ?: '/var/www/html/data',
    'overwrite.cli.url' => getenv('OVERWRITE_CLI_URL') ?: 'http://localhost',
    'htaccess.RewriteBase' => '/',
    'log_type' => 'file',
    'logfile' => '/var/www/html/data/nextcloud.log',
    'loglevel' => getenv('LOG_LEVEL') ?: 2,
    'installed' => false,
    'secret' => getenv('SECRET') ?: '',
];
