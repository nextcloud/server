<?php

require_once('apps/files_versions/versions.php');

OCP\App::registerAdmin('files_versions', 'settings');
OCP\Util::addscript('files_versions', 'versions');

// Listen to write signals
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_write, "OCA_Versions\Storage", "write_hook");
