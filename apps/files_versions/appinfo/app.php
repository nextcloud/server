<?php

//require_once 'files_versions/versions.php';
OC::$CLASSPATH['OCA_Versions\Storage'] = 'apps/files_versions/lib/versions.php';
OC::$CLASSPATH['OCA_Versions\Hooks'] = 'apps/files_versions/lib/hooks.php';

OCP\App::registerAdmin('files_versions', 'settings');
OCP\App::registerPersonal('files_versions', 'settings-personal');

OCP\Util::addscript('files_versions', 'versions');

// Listen to write signals
OCP\Util::connectHook('OC_Filesystem', 'write', "OCA_Versions\Hooks", "write_hook");
// Listen to delete and rename signals
OCP\Util::connectHook('OC_Filesystem', 'post-delete', "OCA_Versions\Hooks", "remove_hook");
OCP\Util::connectHook('OC_Filesystem', 'rename', "OCA_Versions\Hooks", "rename_hook");