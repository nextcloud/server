<?php
$this->create('core_ajax_public_preview', '/publicpreview.png')->action(
function() {
	require_once __DIR__ . '/../ajax/publicpreview.php';
});

// OCS API

//TODO: SET: mail notification, waiting for PR #4689 to be accepted

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

OC_API::register('post',
		'/apps/files_sharing/api/expire/{path}',
		array('\OCA\Files\Share\Api', 'setExpire'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+'));

OC_API::register('post',
		'/apps/files_sharing/api/unshare/{path}',
		array('\OCA\Files\Share\Api', 'setUnshare'),
		'files_sharing',
		OC_API::USER_AUTH,
		array('path' => ''),
		array('path' => '.+'));
