<?php
$CONFIG = [
	'appstoreenabled' => false,
	'apps_paths' => [
		[
			'path'		=> OC::$SERVERROOT . '/apps',
			'url'		=> '/apps',
			'writable'	=> true,
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
		'arguments' => array(
			'bucket' => 'nextcloud',
			'autocreate' => true,
			'key' => 'dummy',
			'secret' => 'dummy',
			'hostname' => getenv('DRONE') === 'true' ? 'fake-s3' : 'localhost',
			'port' => 4569,
			'use_ssl' => false,
			// required for some non amazon s3 implementations
			'use_path_style' => true
		)
	];
}
