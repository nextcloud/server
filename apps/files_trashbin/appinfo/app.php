<?php

OC::$CLASSPATH['OCA_Trash\Hooks'] = 'apps/files_trashbin/lib/hooks.php';
OC::$CLASSPATH['OCA_Trash\Trashbin'] = 'apps/files_trashbin/lib/trash.php';


OCP\Util::connectHook('OC_Filesystem', 'delete', "OCA_Trash\Hooks", "remove_hook");
