<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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

// Privatedata
API::register(
	'get',
	'/privatedata/getattribute',
	array('OC_OCS_Privatedata', 'get'),
	'core',
	API::USER_AUTH,
	array('app' => '', 'key' => '')
	);
API::register(
	'get',
	'/privatedata/getattribute/{app}',
	array('OC_OCS_Privatedata', 'get'),
	'core',
	API::USER_AUTH,
	array('key' => '')
	);
API::register(
	'get',
	'/privatedata/getattribute/{app}/{key}',
	array('OC_OCS_Privatedata', 'get'),
	'core',
	API::USER_AUTH
	);
API::register(
	'post',
	'/privatedata/setattribute/{app}/{key}',
	array('OC_OCS_Privatedata', 'set'),
	'core',
	API::USER_AUTH
	);
API::register(
	'post',
	'/privatedata/deleteattribute/{app}/{key}',
	array('OC_OCS_Privatedata', 'delete'),
	'core',
	API::USER_AUTH
	);
