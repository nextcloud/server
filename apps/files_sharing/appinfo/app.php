<?php

OC::$CLASSPATH['OC_Share_Backend_File'] = "apps/files_sharing/lib/share/file.php";
OC::$CLASSPATH['OC_Share_Backend_Folder'] = 'apps/files_sharing/lib/share/folder.php';
OC::$CLASSPATH['OC\Files\Storage\Shared'] = "apps/files_sharing/lib/sharedstorage.php";
OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');
OCP\Share::registerBackend('file', 'OC_Share_Backend_File');
OCP\Share::registerBackend('folder', 'OC_Share_Backend_Folder', 'file');
OCP\Util::addScript('files_sharing', 'share');
