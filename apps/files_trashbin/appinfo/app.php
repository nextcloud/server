<?php

OC::$CLASSPATH['OCA\Files_Trashbin\Hooks'] = 'apps/files_trashbin/lib/hooks.php';
OC::$CLASSPATH['OCA\Files_Trashbin\Trashbin'] = 'apps/files_trashbin/lib/trash.php';


OCP\Util::connectHook('OC_Filesystem', 'delete', "OCA\Files_Trashbin\Hooks", "remove_hook");
