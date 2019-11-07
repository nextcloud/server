<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Gadzy <dev@gadzy.fr>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

use OCA\Files_Sharing\ShareBackend\File;
use OCA\Files_Sharing\ShareBackend\Folder;
use OCA\Files_Sharing\AppInfo\Application;

\OCA\Files_Sharing\Helper::registerHooks();

\OC\Share\Share::registerBackend('file', File::class);
\OC\Share\Share::registerBackend('folder', Folder::class, 'file');

$application = \OC::$server->query(Application::class);
$application->registerMountProviders();

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		\OCP\Util::addScript('files_sharing', 'dist/additionalScripts');
		\OCP\Util::addStyle('files_sharing', 'icons');
	}
);
\OC::$server->getEventDispatcher()->addListener('\OCP\Collaboration\Resources::loadAdditionalScripts', function () {
	\OCP\Util::addScript('files_sharing', 'dist/collaboration');
});

$config = \OC::$server->getConfig();
$shareManager = \OC::$server->getShareManager();
$userSession = \OC::$server->getUserSession();
$l = \OC::$server->getL10N('files_sharing');

if ($config->getAppValue('core', 'shareapi_enabled', 'yes') === 'yes') {

	$sharingSublistArray = [];

	if (\OCP\Util::isSharingDisabledForUser() === false) {
		array_push($sharingSublistArray, [
			'id' => 'sharingout',
			'appname' => 'files_sharing',
			'script' => 'list.php',
			'order' => 16,
			'name' => $l->t('Shared with others'),
		]);
	}

	array_push($sharingSublistArray, [
		'id' => 'sharingin',
		'appname' => 'files_sharing',
		'script' => 'list.php',
		'order' => 15,
		'name' => $l->t('Shared with you'),
	]);

	if (\OCP\Util::isSharingDisabledForUser() === false) {
		// Check if sharing by link is enabled
		if ($config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes') {
			array_push($sharingSublistArray, [
				'id' => 'sharinglinks',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 17,
				'name' => $l->t('Shared by link'),
			]);
		}
	}

	array_push($sharingSublistArray, [
		'id' => 'deletedshares',
		'appname' => 'files_sharing',
		'script' => 'list.php',
		'order' => 19,
		'name' => $l->t('Deleted shares'),
	]);

	// show_Quick_Access stored as string
	$user = $userSession->getUser();
	$defaultExpandedState = true;
	if ($user instanceof \OCP\IUser) {
		$defaultExpandedState = $config->getUserValue($userSession->getUser()->getUID(), 'files', 'show_sharing_menu', '0') === '1';
	}

	\OCA\Files\App::getNavigationManager()->add([
		'id' => 'shareoverview',
		'appname' => 'files_sharing',
		'script' => 'list.php',
		'order' => 18,
		'name' => $l->t('Shares'),
		'classes' => 'collapsible',
		'sublist' => $sharingSublistArray,
		'expandedState' => 'show_sharing_menu'
	]);
}
