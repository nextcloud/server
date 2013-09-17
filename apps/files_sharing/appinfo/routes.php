<?php
$this->create('core_ajax_public_preview', '/publicpreview.png')->action(
function() {
	require_once __DIR__ . '/../ajax/publicpreview.php';
});

// OCS API

//TODO: SET: mail notification, waiting for PR #4689 to be accepted

OC_API::register('get',
		'/apps/files_sharing/api/shares',
		array('\OCA\Files\Share\Api', 'getAllShare'),
		'files_sharing');

OC_API::register('post',
		'/apps/files_sharing/api/shares',
		array('\OCA\Files\Share\Api', 'createShare'),
		'files_sharing');

OC_API::register('get',
		'/apps/files_sharing/api/shares/{path}',
		array('\OCA\Files\Share\Api', 'getShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+')); //allow slashes in parameter path

OC_API::register('put',
		'/apps/files_sharing/api/shares/{path}',
		array('\OCA\Files\Share\Api', 'updateShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+'));

OC_API::register('delete',
		'/apps/files_sharing/api/shares/{path}',
		array('\OCA\Files\Share\Api', 'deleteShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+'));
