<?php

//OC::$CLASSPATH['OCA\Files_Trashbin\Hooks'] = 'files_trashbin/lib/hooks.php';
//OC::$CLASSPATH['OCA\Files_Trashbin\Trashbin'] = 'files_trashbin/lib/trash.php';
OC::$CLASSPATH['OCA\Files_Trashbin\Exceptions\CopyRecursiveException'] = 'files_trashbin/lib/exceptions.php';


// register hooks
\OCA\Files_Trashbin\Trashbin::registerHooks();
