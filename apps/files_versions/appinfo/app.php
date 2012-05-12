<?php

require_once('apps/files_versions/versions.php');

// Add an entry in the app list
OCP\App::register( array(
  'order' => 10,
  'id' => 'files_versions',
  'name' => 'Versioning' ));

OCP\App::registerAdmin('files_versions', 'settings');
OCP\Util::addscript('files_versions', 'versions');

// Listen to write signals
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_write, "OCA_Versions\Storage", "write_hook");

?>
