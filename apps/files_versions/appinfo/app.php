<?php

//require_once 'files_versions/versions.php';
OC::$CLASSPATH['OCA\Files_Versions\Storage'] = 'files_versions/lib/versions.php';
OC::$CLASSPATH['OCA\Files_Versions\Hooks'] = 'files_versions/lib/hooks.php';
OC::$CLASSPATH['OCA\Files_Versions\Capabilities'] = 'files_versions/lib/capabilities.php';

OCP\Util::addscript('files_versions', 'versions');
OCP\Util::addStyle('files_versions', 'versions');

\OCA\Files_Versions\Hooks::connectHooks();
