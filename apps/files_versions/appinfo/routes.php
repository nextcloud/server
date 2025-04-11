<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\AppInfo;

use OCP\Route\IRouter;

/** @var IRouter $this */
$this->create('files_versions_download', 'apps/files_versions/download.php')
	->actionInclude('files_versions/download.php');
$this->create('files_versions_ajax_getVersions', 'apps/files_versions/ajax/getVersions.php')
	->actionInclude('files_versions/ajax/getVersions.php');
$this->create('files_versions_ajax_rollbackVersion', 'apps/files_versions/ajax/rollbackVersion.php')
	->actionInclude('files_versions/ajax/rollbackVersion.php');

return [
	'routes' => [
		[
			'name' => 'Preview#getPreview',
			'url' => '/preview',
			'verb' => 'GET',
		],
	],
];
