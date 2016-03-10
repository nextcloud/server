<?php

$l = \OC::$server->getL10N('files_external_ftp');

OC_Mount_Config::registerBackend('\OCA\Files_External_FTP\FTP', [
	'backend' => (string)$l->t('FTP (Fly)'),
	'priority' => 100,
	'configuration' => [
		'host' => (string)$l->t('hostname'),
		'username' => (string)$l->t('Username'),
		'password' => (string)$l->t('Password'),
		'root' => '&' . $l->t('Remote subfolder'),
		'ssl' => '!' . $l->t('Secure ftps://'),
		'port' => '&' . $l->t('Port'),
	],
]);
