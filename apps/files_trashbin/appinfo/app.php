<?php

OC::$CLASSPATH['OCA\Files_Trashbin\Hooks'] = 'files_trashbin/lib/hooks.php';
OC::$CLASSPATH['OCA\Files_Trashbin\Trashbin'] = 'files_trashbin/lib/trash.php';

//Listen to delete file signal
OCP\Util::connectHook('OC_Filesystem', 'delete', "OCA\Files_Trashbin\Hooks", "remove_hook");
//Listen to delete user signal
OCP\Util::connectHook('OC_User', 'pre_deleteUser', "OCA\Files_Trashbin\Hooks", "deleteUser_hook");