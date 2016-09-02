<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCP\API;

$application = new \OCA\Files_Sharing\AppInfo\Application();
$application->registerRoutes($this, [
	'resources' => [
		'ExternalShares' => ['url' => '/api/externalShares'],
	],
	'routes' => [
		[
			'name' => 'externalShares#testRemote',
			'url' => '/testremote',
			'verb' => 'GET'
		],
	],
	'ocs' => [
		/*
		 * OCS Share API
		 */
		[
			'name' => 'ShareAPI#getShares',
			'url'  => '/api/v1/shares',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareAPI#createShare',
			'url'  => '/api/v1/shares',
			'verb' => 'POST',
		],
		[
			'name' => 'ShareAPI#getShare',
			'url'  => '/api/v1/shares/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareAPI#updateShare',
			'url'  => '/api/v1/shares/{id}',
			'verb' => 'PUT',
		],
		[
			'name' => 'ShareAPI#deleteShare',
			'url'  => '/api/v1/shares/{id}',
			'verb' => 'DELETE',
		],
		/*
		 * OCS Sharee API
		 */
		[
			'name' => 'ShareesAPI#search',
			'url' => '/api/v1/sharees',
			'verb' => 'GET',
		],
		/*
		 * Remote Shares
		 */
		[
			'name' => 'Remote#getShares',
			'url' => '/api/v1/remote_shares',
			'verb' => 'GET',
		],
		[
			'name' => 'Remote#getOpenShares',
			'url' => '/api/v1/remote_shares/pending',
			'verb' => 'GET',
		],
		[
			'name' => 'Remote#acceptShare',
			'url' => '/api/v1/remote_shares/pending/{id}',
			'verb' => 'POST',
		],
		[
			'name' => 'Remote#declineShare',
			'url' => '/api/v1/remote_shares/pending/{id}',
			'verb' => 'DELETE',
		],
		[
			'name' => 'Remote#getShare',
			'url' => '/api/v1/remote_shares/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'Remote#unshare',
			'url' => '/api/v1/remote_shares/{id}',
			'verb' => 'DELETE',
		],
	],
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

// OCS API

//TODO: SET: mail notification, waiting for PR #4689 to be accepted
