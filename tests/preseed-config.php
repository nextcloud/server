<?php

$CONFIG = [
	'appstoreenabled' => false,
	'apps_paths' => [
		[
			'path' => OC::$SERVERROOT . '/apps',
			'url' => '/apps',
			'writable' => true,
		],
	],
];

if (is_dir(OC::$SERVERROOT.'/apps2')) {
	$CONFIG['apps_paths'][] = [
		'path' => OC::$SERVERROOT . '/apps2',
		'url' => '/apps2',
		'writable' => false,
	];
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
