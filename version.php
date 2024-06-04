<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// We only can count up. The 4. digit is only for the internal patch level to trigger DB upgrades
// between betas, final and RCs. This is _not_ the public version number. Reset minor/patch level
// when updating major/minor version number.

$OC_Version = [30, 0, 0, 1];

// The human-readable string
$OC_VersionString = '30.0.0 dev';

$OC_VersionCanBeUpgradedFrom = [
	'nextcloud' => [
		'29.0' => true,
		'30.0' => true,
	],
	'owncloud' => [
		'10.13' => true,
	],
];

// default Nextcloud channel
$OC_Channel = 'git';

// The build number
$OC_Build = '';

// Vendor of this package
$vendor = 'nextcloud';
