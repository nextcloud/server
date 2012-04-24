<?php

require_once('apps/files_versions/versions.php');

// Add an entry in the app list
OC_App::register( array(
  'order' => 10,
  'id' => 'files_versions',
  'name' => 'Versioning' ));

OC_APP::registerAdmin('files_versions', 'settings');

// Listen to write signals
OC_Hook::connect(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_write, "OCA_Versions\Storage", "write_hook");




?>
