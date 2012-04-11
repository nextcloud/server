<?php

// Include required files
require_once('apps/files_versioning/versionstorage.php');
require_once('apps/files_versioning/versionwrapper.php');
// Register streamwrapper for versioned:// paths
stream_wrapper_register('versioned', 'OC_VersionStreamWrapper');

// Add an entry in the app list for versioning and backup
OC_App::register( array(
  'order' => 10,
  'id' => 'files_versioning',
  'name' => 'Versioning and Backup' ));

// Include stylesheets for the settings page
OC_Util::addStyle( 'files_versioning', 'settings' );
OC_Util::addScript('files_versioning','settings');

// Register a settings section in the Admin > Personal page
OC_APP::registerPersonal('files_versioning','settings');
