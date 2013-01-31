<?php

OC::$CLASSPATH['OC_Share_Backend_File'] = "apps/files_sharing/lib/share/file.php";
OC::$CLASSPATH['OC_Share_Backend_Folder'] = 'apps/files_sharing/lib/share/folder.php';
OC::$CLASSPATH['OC\Files\Storage\Shared'] = "apps/files_sharing/lib/sharedstorage.php";
OC::$CLASSPATH['OC\Files\Cache\Shared_Cache'] = 'apps/files_sharing/lib/cache.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Permissions'] = 'apps/files_sharing/lib/permissions.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Watcher'] = 'apps/files_sharing/lib/watcher.php';
OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');
OCP\Share::registerBackend('file', 'OC_Share_Backend_File');
OCP\Share::registerBackend('folder', 'OC_Share_Backend_Folder', 'file');
OCP\Util::addScript('files_sharing', 'share');
