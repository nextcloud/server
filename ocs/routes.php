<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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
