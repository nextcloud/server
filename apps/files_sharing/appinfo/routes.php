<?php
$this->create('core_ajax_public_preview', '/publicpreview.png')->action(
function() {
	require_once __DIR__ . '/../ajax/publicpreview.php';
});

//TODO: SET: unshare
//TODO: SET: permissions
//TODO: SET: expire date
//TODO: SET: mail notification
//TODO: SET: can upload

OC_API::register('get',
		'/apps/files_sharing/api/share/{path}',
		array('\OCA\Files\Share\Api', 'getShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+')); //allow slashes in parameter path

OC_API::register('post',
		'/apps/files_sharing/api/share/{path}',
		array('\OCA\Files\Share\Api', 'setShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+'));

OC_API::register('post',
		'/apps/files_sharing/api/permission/{path}',
		array('\OCA\Files\Share\Api', 'setPermission'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+'));

/*
OC_API::register('get',
		'/apps/files_sharing/api/permission/{path}',
		array('\OCA\Files\Share\Api', 'getShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''));

OC_API::register('get',
		'/apps/files_sharing/api/expire/{path}',
		array('\OCA\Files\Share\Api', 'getShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''));

OC_API::register('get',
		'/apps/files_sharing/api/notify/{path}',
		array('\OCA\Files\Share\Api', 'getShare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''));
*/
