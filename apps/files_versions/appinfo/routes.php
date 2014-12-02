<?php
/**
 * Copyright (c) 2013, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/** @var $this \OCP\Route\IRouter */
$this->create('core_ajax_versions_preview', '/preview')->action(
function() {
	require_once __DIR__ . '/../ajax/preview.php';
});

$this->create('files_versions_download', 'download.php')
	->actionInclude('files_versions/download.php');
$this->create('files_versions_ajax_getVersions', 'ajax/getVersions.php')
	->actionInclude('files_versions/ajax/getVersions.php');
$this->create('files_versions_ajax_rollbackVersion', 'ajax/rollbackVersion.php')
	->actionInclude('files_versions/ajax/rollbackVersion.php');

// Register with the capabilities API
OC_API::register('get', '/cloud/capabilities', array('OCA\Files_Versions\Capabilities', 'getCapabilities'), 'files_versions', OC_API::USER_AUTH);
