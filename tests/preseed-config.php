<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
$CONFIG = [
	'appstoreenabled' => false,
	// Argon2 at its minimum cost. The suite creates users constantly and the
	// default parameters make every password hash take ~100ms.
	'hashingMemoryCost' => 8,
	'hashingTimeCost' => 1,
	'hashingThreads' => 1,
	// Only used when argon2 is unavailable and bcrypt is the fallback.
	'hashingCost' => 4,
	// Smaller keys are faster to generate and the tests do not need 4096 bit.
	'openssl' => ['private_key_bits' => 2048],
	'apps_paths' => [],
	'tempdirectory' => '/dev/shm',
];

// Roots for apps explicitly requested via EXTRA_APPS_PATHS are registered
// first: when the same app id exists under multiple roots (e.g. a stock
// copy already shipped in apps/), OC_App::findAppInDirectories() prefers
// the highest appinfo version and, on a tie, the first registered root -
// so the app under test has to be searched before apps/.
$appsPaths = [];

$extraAppsPaths = getenv('EXTRA_APPS_PATHS');
if ($extraAppsPaths !== false && $extraAppsPaths !== '') {
	foreach (explode(PATH_SEPARATOR, $extraAppsPaths) as $extraAppsPath) {
		// relative paths are resolved against the server root, not the
		// current working directory, since config.php is read back by
		// processes started from a different directory (e.g. tests/)
		if (!str_starts_with($extraAppsPath, '/')) {
			$extraAppsPath = OC::$SERVERROOT . '/' . $extraAppsPath;
		}
		// EXTRA_APPS_PATHS names the app's own directory; findAppInDirectories()
		// looks for "<root>/<app id>", so the root to register is its parent.
		$extraAppsRoot = dirname($extraAppsPath);
		if (is_dir($extraAppsPath)) {
			$appsPaths[] = [
				'path' => $extraAppsRoot,
				'url' => '/' . basename($extraAppsRoot),
				'writable' => false,
			];
		}
	}
}

$appsPaths[] = [
	'path' => OC::$SERVERROOT . '/apps',
	'url' => '/apps',
	'writable' => true,
];

if (is_dir(OC::$SERVERROOT . '/apps2')) {
	$appsPaths[] = [
		'path' => OC::$SERVERROOT . '/apps2',
		'url' => '/apps2',
		'writable' => false,
	];
}

if (is_dir(OC::$SERVERROOT . '/apps-extra')) {
	$appsPaths[] = [
		'path' => OC::$SERVERROOT . '/apps-extra',
		'url' => '/apps-extra',
		'writable' => false,
	];
}

$seenAppsPaths = [];
foreach ($appsPaths as $appsPath) {
	if (isset($seenAppsPaths[$appsPath['path']])) {
		continue;
	}
	$seenAppsPaths[$appsPath['path']] = true;
	$CONFIG['apps_paths'][] = $appsPath;
}

if (getenv('OBJECT_STORE') === 's3') {
	$CONFIG['objectstore'] = [
		'class' => 'OC\\Files\\ObjectStore\\S3',
		'arguments' => [
			'bucket' => 'nextcloud',
			'autocreate' => true,
			'key' => getenv('OBJECT_STORE_KEY') ?: 'nextcloud',
			'secret' => getenv('OBJECT_STORE_SECRET') ?: 'nextcloud',
			'hostname' => getenv('OBJECT_STORE_HOST') ?: 'localhost',
			'port' => 9000,
			'use_ssl' => false,
			// required for some non amazon s3 implementations
			'use_path_style' => true
		]
	];
} elseif (getenv('OBJECT_STORE') === 's3-multibucket') {
	$CONFIG['objectstore_multibucket'] = [
		'class' => 'OC\\Files\\ObjectStore\\S3',
		'arguments' => [
			'bucket' => 'nextcloud',
			'autocreate' => true,
			'key' => getenv('OBJECT_STORE_KEY') ?: 'nextcloud',
			'secret' => getenv('OBJECT_STORE_SECRET') ?: 'nextcloud',
			'hostname' => getenv('OBJECT_STORE_HOST') ?: 'localhost',
			'port' => 9000,
			'use_ssl' => false,
			// required for some non amazon s3 implementations
			'use_path_style' => true
		]
	];
} elseif (getenv('OBJECT_STORE') === 'azure') {
	$CONFIG['objectstore'] = [
		'class' => 'OC\\Files\\ObjectStore\\Azure',
		'arguments' => [
			'container' => 'test',
			'account_name' => getenv('OBJECT_STORE_KEY') ?: 'devstoreaccount1',
			'account_key' => getenv('OBJECT_STORE_SECRET') ?: 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==',
			'endpoint' => 'http://' . (getenv('OBJECT_STORE_HOST') ?: 'localhost') . ':10000/' . (getenv('OBJECT_STORE_KEY') ?: 'devstoreaccount1'),
			'autocreate' => true
		]
	];
} elseif (getenv('OBJECT_STORE') === 'swift') {
	$swiftHost = getenv('OBJECT_STORE_HOST') ?: 'localhost:5000';

	$CONFIG['objectstore'] = [
		'class' => 'OC\\Files\\ObjectStore\\Swift',
		'arguments' => [
			'autocreate' => true,
			'user' => [
				'name' => getenv('OBJECT_STORE_KEY') ?: 'swift',
				'password' => getenv('OBJECT_STORE_SECRET') ?: 'swift',
				'domain' => [
					'name' => 'Default',
				],
			],
			'scope' => [
				'project' => [
					'name' => 'service',
					'domain' => [
						'name' => 'Default',
					],
				],
			],
			'serviceName' => 'service',
			'region' => 'RegionOne',
			'url' => "http://$swiftHost/v3",
			'bucket' => 'nextcloud',
		]
	];
}

if (getenv('SHARDING') == '1') {
	$CONFIG['dbsharding'] = [
		'filecache' => [
			'shards' => [
				[
					'port' => 5001,
				],
				[
					'port' => 5002,
				],
				[
					'port' => 5003,
				],
				[
					'port' => 5004,
				],
			]
		]
	];
}
