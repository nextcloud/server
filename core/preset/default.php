<?php

return [
	'core' => [
		'appConfig' => [
			'!loadedConfigPreset' => 'default',
		],
		'userConfig' => [
		],
	],
	'any_app' => [
		'__appEnabledAtInstall' => true, // enable app right after the end of the Nextcloud installation process
		'__appLocked' => true, // lock the status of the app, disabling the enable/disable status switching
		'appConfig' => [
			'any_key' => 'value',
		],
		'userConfig' => [
		],
	],
];
