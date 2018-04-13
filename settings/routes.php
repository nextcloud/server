<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Raghu Nayyar <me@iraghu.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Pulzer <t.pulzer@kniel.de>
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

namespace OC\Settings;

$application = new Application();
$application->registerRoutes($this, [
	'resources' => [
		'AuthSettings' => ['url' => '/settings/personal/authtokens'],
	],
	'routes' => [
		['name' => 'MailSettings#setMailSettings', 'url' => '/settings/admin/mailsettings', 'verb' => 'POST'],
		['name' => 'MailSettings#storeCredentials', 'url' => '/settings/admin/mailsettings/credentials', 'verb' => 'POST'],
		['name' => 'MailSettings#sendTestMail', 'url' => '/settings/admin/mailtest', 'verb' => 'POST'],
		['name' => 'Encryption#startMigration', 'url' => '/settings/admin/startmigration', 'verb' => 'POST'],
		['name' => 'AppSettings#listCategories', 'url' => '/settings/apps/categories', 'verb' => 'GET'],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps', 'verb' => 'GET'],
		['name' => 'AppSettings#listApps', 'url' => '/settings/apps/list', 'verb' => 'GET'],
		['name' => 'Users#setUserSettings', 'url' => '/settings/users/{username}/settings', 'verb' => 'PUT'],
		['name' => 'Users#getVerificationCode', 'url' => '/settings/users/{account}/verify', 'verb' => 'GET'],
		['name' => 'Users#usersList', 'url' => '/settings/users', 'verb' => 'GET'],
		['name' => 'Users#usersListByGroup', 'url' => '/settings/users/{group}', 'verb' => 'GET'],
		['name' => 'LogSettings#setLogLevel', 'url' => '/settings/admin/log/level', 'verb' => 'POST'],
		['name' => 'LogSettings#getEntries', 'url' => '/settings/admin/log/entries', 'verb' => 'GET'],
		['name' => 'LogSettings#download', 'url' => '/settings/admin/log/download', 'verb' => 'GET'],
		['name' => 'CheckSetup#check', 'url' => '/settings/ajax/checksetup', 'verb' => 'GET'],
		['name' => 'CheckSetup#getFailedIntegrityCheckFiles', 'url' => '/settings/integrity/failed', 'verb' => 'GET'],
		['name' => 'CheckSetup#rescanFailedIntegrityCheck', 'url' => '/settings/integrity/rescan', 'verb' => 'GET'],
		['name' => 'Certificate#addPersonalRootCertificate', 'url' => '/settings/personal/certificate', 'verb' => 'POST'],
		['name' => 'Certificate#removePersonalRootCertificate', 'url' => '/settings/personal/certificate/{certificateIdentifier}', 'verb' => 'DELETE'],
		['name' => 'Certificate#addSystemRootCertificate', 'url' => '/settings/admin/certificate', 'verb' => 'POST'],
		['name' => 'Certificate#removeSystemRootCertificate', 'url' => '/settings/admin/certificate/{certificateIdentifier}', 'verb' => 'DELETE'],
		['name' => 'PersonalSettings#index', 'url' => '/settings/user/{section}', 'verb' => 'GET', 'defaults' => ['section' => 'personal-info']],
		['name' => 'AdminSettings#index', 'url' => '/settings/admin/{section}', 'verb' => 'GET', 'defaults' => ['section' => 'server']],
		['name' => 'AdminSettings#form', 'url' => '/settings/admin/{section}', 'verb' => 'GET'],
		['name' => 'ChangePassword#changePersonalPassword', 'url' => '/settings/personal/changepassword', 'verb' => 'POST'],
		['name' => 'ChangePassword#changeUserPassword', 'url' => '/settings/users/changepassword', 'verb' => 'POST']
	]
]);

/** @var $this \OCP\Route\IRouter */

// Settings pages
$this->create('settings_help', '/settings/help')
	->actionInclude('settings/help.php');
// Settings ajax actions
// apps
$this->create('settings_ajax_enableapp', '/settings/ajax/enableapp.php')
	->actionInclude('settings/ajax/enableapp.php');
$this->create('settings_ajax_disableapp', '/settings/ajax/disableapp.php')
	->actionInclude('settings/ajax/disableapp.php');
$this->create('settings_ajax_updateapp', '/settings/ajax/updateapp.php')
	->actionInclude('settings/ajax/updateapp.php');
$this->create('settings_ajax_uninstallapp', '/settings/ajax/uninstallapp.php')
	->actionInclude('settings/ajax/uninstallapp.php');
