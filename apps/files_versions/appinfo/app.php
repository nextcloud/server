<?php

require_once('files_versions/versions.php');

OCP\App::registerAdmin('files_versions', 'settings');
OCP\App::registerPersonal('files_versions','settings-personal');

OCP\Util::addscript('files_versions', 'versions');

// Listen to write signals
OCP\Util::connectHook('OC_Filesystem', 'post_write', "OCA_Versions\Storage", "write_hook");
// Listen to delete and rename signals
OCP\Util::connectHook('OC_Filesystem', 'delete', "OCA_Versions\Storage", "removeVersions");
OCP\Util::connectHook('OC_Filesystem', 'rename', "OCA_Versions\Storage", "renameVersions");