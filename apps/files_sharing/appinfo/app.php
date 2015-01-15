<?php
$l = \OC::$server->getL10N('files_sharing');

OC::$CLASSPATH['OC_Share_Backend_File'] = 'files_sharing/lib/share/file.php';
OC::$CLASSPATH['OC_Share_Backend_Folder'] = 'files_sharing/lib/share/folder.php';
OC::$CLASSPATH['OC\Files\Storage\Shared'] = 'files_sharing/lib/sharedstorage.php';
OC::$CLASSPATH['OC\Files\Cache\SharedScanner'] = 'files_sharing/lib/scanner.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Cache'] = 'files_sharing/lib/cache.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Permissions'] = 'files_sharing/lib/permissions.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Updater'] = 'files_sharing/lib/updater.php';
OC::$CLASSPATH['OC\Files\Cache\Shared_Watcher'] = 'files_sharing/lib/watcher.php';
OC::$CLASSPATH['OCA\Files\Share\Maintainer'] = 'files_sharing/lib/maintainer.php';
OC::$CLASSPATH['OCA\Files\Share\Proxy'] = 'files_sharing/lib/proxy.php';

// Exceptions
OC::$CLASSPATH['OCA\Files_Sharing\Exceptions\BrokenPath'] = 'files_sharing/lib/exceptions.php';

\OCP\App::registerAdmin('files_sharing', 'settings-admin');

\OCA\Files_Sharing\Helper::registerHooks();

OCP\Share::registerBackend('file', 'OC_Share_Backend_File');
OCP\Share::registerBackend('folder', 'OC_Share_Backend_Folder', 'file');

OCP\Util::addScript('files_sharing', 'share');
OCP\Util::addScript('files_sharing', 'external');

OC_FileProxy::register(new OCA\Files\Share\Proxy());

\OC::$server->getActivityManager()->registerExtension(function() {
		return new \OCA\Files_Sharing\Activity();
});

$config = \OC::$server->getConfig();
if ($config->getAppValue('core', 'shareapi_enabled', 'yes') === 'yes') {

	\OCA\Files\App::getNavigationManager()->add(
		array(
			"id" => 'sharingin',
			"appname" => 'files_sharing',
			"script" => 'list.php',
			"order" => 10,
			"name" => $l->t('Shared with you')
		)
	);

	if (\OCP\Util::isSharingDisabledForUser() === false) {

		\OCA\Files\App::getNavigationManager()->add(
			array(
				"id" => 'sharingout',
				"appname" => 'files_sharing',
				"script" => 'list.php',
				"order" => 15,
				"name" => $l->t('Shared with others')
			)
		);
		// Check if sharing by link is enabled
		if ($config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes') {
			\OCA\Files\App::getNavigationManager()->add(
				array(
					"id" => 'sharinglinks',
					"appname" => 'files_sharing',
					"script" => 'list.php',
					"order" => 20,
					"name" => $l->t('Shared by link')
				)
			);
		}
	}
}
