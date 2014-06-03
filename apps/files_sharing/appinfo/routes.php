<?php
/** @var $this \OCP\Route\IRouter */
$this->create('core_ajax_public_preview', '/publicpreview')->action(
	function() {
		require_once __DIR__ . '/../ajax/publicpreview.php';
	});

use \OCA\Files_Sharing\App\Sharing;

$app = new Sharing();

$app->registerRoutes($this, array('routes' => array(

	// mailTemplate settings
	array('name' => 'admin_settings#render', 'url' => '/settings/mailtemplate', 'verb' => 'GET'),

	array('name' => 'admin_settings#update', 'url' => '/settings/mailtemplate', 'verb' => 'POST'),

	array('name' => 'admin_settings#reset', 'url' => '/settings/mailtemplate', 'verb' => 'DELETE')

)));

// OCS API

//TODO: SET: mail notification, waiting for PR #4689 to be accepted

OC_API::register('get',
		'/apps/files_sharing/api/v1/shares',
		array('\OCA\Files\Share\Api', 'getAllShares'),
		'files_sharing');

OC_API::register('post',
		'/apps/files_sharing/api/v1/shares',
		array('\OCA\Files\Share\Api', 'createShare'),
		'files_sharing');

OC_API::register('get',
		'/apps/files_sharing/api/v1/shares/{id}',
		array('\OCA\Files\Share\Api', 'getShare'),
		'files_sharing');

OC_API::register('put',
		'/apps/files_sharing/api/v1/shares/{id}',
		array('\OCA\Files\Share\Api', 'updateShare'),
		'files_sharing');

OC_API::register('delete',
		'/apps/files_sharing/api/v1/shares/{id}',
		array('\OCA\Files\Share\Api', 'deleteShare'),
		'files_sharing');
