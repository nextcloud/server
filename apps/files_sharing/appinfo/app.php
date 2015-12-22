<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Gadzy <dev@gadzy.fr>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Appinfo;

$l = \OC::$server->getL10N('files_sharing');

\OC::$CLASSPATH['OC_Share_Backend_File'] = 'files_sharing/lib/share/file.php';
\OC::$CLASSPATH['OC_Share_Backend_Folder'] = 'files_sharing/lib/share/folder.php';
\OC::$CLASSPATH['OC\Files\Storage\Shared'] = 'files_sharing/lib/sharedstorage.php';
\OC::$CLASSPATH['OC\Files\Cache\SharedScanner'] = 'files_sharing/lib/scanner.php';
\OC::$CLASSPATH['OC\Files\Cache\Shared_Cache'] = 'files_sharing/lib/cache.php';
\OC::$CLASSPATH['OC\Files\Cache\Shared_Permissions'] = 'files_sharing/lib/permissions.php';
\OC::$CLASSPATH['OC\Files\Cache\Shared_Updater'] = 'files_sharing/lib/updater.php';
\OC::$CLASSPATH['OC\Files\Cache\Shared_Watcher'] = 'files_sharing/lib/watcher.php';
\OC::$CLASSPATH['OCA\Files\Share\Maintainer'] = 'files_sharing/lib/maintainer.php';
\OC::$CLASSPATH['OCA\Files\Share\Proxy'] = 'files_sharing/lib/proxy.php';

$application = new Application();
$application->registerMountProviders();
$application->setupPropagation();

\OCP\App::registerAdmin('files_sharing', 'settings-admin');
\OCP\App::registerPersonal('files_sharing', 'settings-personal');

\OCA\Files_Sharing\Helper::registerHooks();

\OCP\Share::registerBackend('file', 'OC_Share_Backend_File');
\OCP\Share::registerBackend('folder', 'OC_Share_Backend_Folder', 'file');

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		\OCP\Util::addScript('files_sharing', 'share');
		\OCP\Util::addScript('files_sharing', 'sharetabview');
		if (\OC::$server->getConfig()->getAppValue('files_sharing', 'incoming_server2server_share_enabled', 'yes') === 'yes') {
			\OCP\Util::addScript('files_sharing', 'external');
		}
		\OCP\Util::addStyle('files_sharing', 'sharetabview');
	}
);

// \OCP\Util::addStyle('files_sharing', 'sharetabview');

\OC::$server->getActivityManager()->registerExtension(function() {
		return new \OCA\Files_Sharing\Activity(
			\OC::$server->query('L10NFactory'),
			\OC::$server->getURLGenerator(),
			\OC::$server->getActivityManager()
		);
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

/**
 * FIXME
$manager = \OC::$server->getNotificationManager();
$manager->registerNotifier(function() {
	return new \OCA\Files_Sharing\Notifier(
		\OC::$server->getL10NFactory()
	);
});
 */
