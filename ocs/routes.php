<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Config
OC_API::register(
	'get',
	'/config',
	array('OC_OCS_Config', 'apiConfig'),
	'core',
	OC_API::GUEST_AUTH
	);
// Person
OC_API::register(
	'post',
	'/person/check',
	array('OC_OCS_Person', 'check'),
	'core',
	OC_API::GUEST_AUTH
	);
// Privatedata
OC_API::register(
	'get',
	'/privatedata/getattribute',
	array('OC_OCS_Privatedata', 'get'),
	'core',
	OC_API::USER_AUTH,
	array('app' => '', 'key' => '')
	);
OC_API::register(
	'get',
	'/privatedata/getattribute/{app}',
	array('OC_OCS_Privatedata', 'get'),
	'core',
	OC_API::USER_AUTH,
	array('key' => '')
	);
OC_API::register(
	'get',
	'/privatedata/getattribute/{app}/{key}',
	array('OC_OCS_Privatedata', 'get'),
	'core',
	OC_API::USER_AUTH
	);
OC_API::register(
	'post',
	'/privatedata/setattribute/{app}/{key}',
	array('OC_OCS_Privatedata', 'set'),
	'core',
	OC_API::USER_AUTH
	);
OC_API::register(
	'post',
	'/privatedata/deleteattribute/{app}/{key}',
	array('OC_OCS_Privatedata', 'delete'),
	'core',
	OC_API::USER_AUTH
	);
// cloud
OC_API::register(
	'get',
	'/cloud/capabilities',
	array('OC_OCS_Cloud', 'getCapabilities'),
	'core',
	OC_API::USER_AUTH
	);
OC_API::register(
	'get',
	'/cloud/users/{userid}',
	array('OC_OCS_Cloud', 'getUser'),
	'core',
	OC_API::USER_AUTH
);
OC_API::register(
	'get',
	'/cloud/user',
	array('OC_OCS_Cloud', 'getCurrentUser'),
	'core',
	OC_API::USER_AUTH
);

// Server-to-Server Sharing
$s2s = new \OCA\Files_Sharing\API\Server2Server();
OC_API::register('post',
		'/cloud/shares',
		array($s2s, 'createShare'),
		'files_sharing',
		OC_API::GUEST_AUTH
);

OC_API::register('post',
		'/cloud/shares/{id}/accept',
		array($s2s, 'acceptShare'),
		'files_sharing',
		OC_API::GUEST_AUTH
);

OC_API::register('post',
		'/cloud/shares/{id}/decline',
		array($s2s, 'declineShare'),
		'files_sharing',
		OC_API::GUEST_AUTH
);

OC_API::register('post',
		'/cloud/shares/{id}/unshare',
		array($s2s, 'unshare'),
		'files_sharing',
		OC_API::GUEST_AUTH
);
