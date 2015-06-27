<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Files_Sharing\AppInfo;

use OCP\API;

$application = new Application();
$application->registerRoutes($this, [
	'resources' => [
		'ExternalShares' => ['url' => '/api/externalShares'],
	]
]);

/** @var $this \OCP\Route\IRouter */
$this->create('core_ajax_public_preview', '/publicpreview')->action(
	function() {
		require_once __DIR__ . '/../ajax/publicpreview.php';
	});

$this->create('files_sharing_ajax_list', 'ajax/list.php')
	->actionInclude('files_sharing/ajax/list.php');
$this->create('files_sharing_ajax_publicpreview', 'ajax/publicpreview.php')
	->actionInclude('files_sharing/ajax/publicpreview.php');
$this->create('sharing_external_shareinfo', '/shareinfo')
	->actionInclude('files_sharing/ajax/shareinfo.php');
$this->create('sharing_external_add', '/external')
	->actionInclude('files_sharing/ajax/external.php');
$this->create('sharing_external_test_remote', '/testremote')
	->actionInclude('files_sharing/ajax/testremote.php');

// OCS API

//TODO: SET: mail notification, waiting for PR #4689 to be accepted

API::register('get',
		'/apps/files_sharing/api/v1/shares',
		array('\OCA\Files_Sharing\API\Local', 'getAllShares'),
		'files_sharing');

API::register('post',
		'/apps/files_sharing/api/v1/shares',
		array('\OCA\Files_Sharing\API\Local', 'createShare'),
		'files_sharing');

API::register('get',
		'/apps/files_sharing/api/v1/shares/{id}',
		array('\OCA\Files_Sharing\API\Local', 'getShare'),
		'files_sharing');

API::register('put',
		'/apps/files_sharing/api/v1/shares/{id}',
		array('\OCA\Files_Sharing\API\Local', 'updateShare'),
		'files_sharing');

API::register('delete',
		'/apps/files_sharing/api/v1/shares/{id}',
		array('\OCA\Files_Sharing\API\Local', 'deleteShare'),
		'files_sharing');

// Register with the capabilities API
API::register('get',
		'/cloud/capabilities',
		array('OCA\Files_Sharing\Capabilities', 'getCapabilities'),
		'files_sharing', API::USER_AUTH);
