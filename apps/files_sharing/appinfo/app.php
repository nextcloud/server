<?php

OC::$CLASSPATH['OC_Share_Backend_File'] = 'files_sharing/lib/share/file.php';
OC::$CLASSPATH['OC_Share_Backend_Folder'] = 'files_sharing/lib/share/folder.php';
OC::$CLASSPATH['OC\Files\Storage\Shared'] = 'files_sharing/lib/sharedstorage.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Cache'] = 'files_sharing/lib/cache.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Permissions'] = 'files_sharing/lib/permissions.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Updater'] = 'files_sharing/lib/updater.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Watcher'] = 'files_sharing/lib/watcher.php';
OC::$CLASSPATH['OCA\Files\Share\Api'] = 'files_sharing/lib/api.php';
OC::$CLASSPATH['OCA\Files\Share\Maintainer'] = 'files_sharing/lib/maintainer.php';
OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');
OCP\Share::registerBackend('file', 'OC_Share_Backend_File');
OCP\Share::registerBackend('folder', 'OC_Share_Backend_Folder', 'file');
OCP\Util::addScript('files_sharing', 'share');
\OC_Hook::connect('OC_Filesystem', 'post_write', '\OC\Files\Cache\Shared_Updater', 'writeHook');
\OC_Hook::connect('OC_Filesystem', 'delete', '\OC\Files\Cache\Shared_Updater', 'deleteHook');
\OC_Hook::connect('OC_Filesystem', 'post_rename', '\OC\Files\Cache\Shared_Updater', 'renameHook');
\OC_Hook::connect('OCP\Share', 'post_shared', '\OC\Files\Cache\Shared_Updater', 'shareHook');
\OC_Hook::connect('OCP\Share', 'pre_unshare', '\OC\Files\Cache\Shared_Updater', 'shareHook');
\OC_Hook::connect('OC_Appconfig', 'post_set_value', '\OCA\Files\Share\Maintainer', 'configChangeHook');
