<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Vincent Petry <pvince81@owncloud.com>
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
// We only can count up. The 4. digit is only for the internal patchlevel to trigger DB upgrades
// between betas, final and RCs. This is _not_ the public version number. Reset minor/patchlevel
// when updating major/minor version number.

$OC_Version = array(12, 0, 13, 2);

// The human readable string
$OC_VersionString = '12.0.13';

$OC_VersionCanBeUpgradedFrom = [
	'nextcloud' => [
		'11.0' => true,
		'12.0' => true,
	],
	'owncloud' => [
		'10.0.0.12' => true,
		'10.0.1.5' => true,
		'10.0.2.1' => true,
		'10.0.3.3' => true,
		'10.0.4.4' => true,
		'10.0.5.4' => true,
		'10.0.6.1' => true,
		'10.0.7.2' => true,
		'10.0.8.5' => true,
		'10.0.9.5' => true,
		'10.0.10.4' => true,
		'10.0.11' => true, // TODO adjust to 4 digits once it is final
		'10.0.12' => true, // TODO adjust to 4 digits once it is final
		'10.0.13' => true, // TODO adjust to 4 digits once it is final
		'10.0.14' => true, // TODO adjust to 4 digits once it is final
		'10.0.15' => true, // TODO adjust to 4 digits once it is final
		'10.0.16' => true, // TODO adjust to 4 digits once it is final
		'10.0.17' => true, // TODO adjust to 4 digits once it is final
	],
];

// default Nextcloud channel
$OC_Channel = 'git';

// The build number
$OC_Build = '';

// Vendor of this package
$vendor = 'nextcloud';
