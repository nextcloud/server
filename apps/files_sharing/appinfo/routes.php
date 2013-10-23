<?php
// OCS API

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
