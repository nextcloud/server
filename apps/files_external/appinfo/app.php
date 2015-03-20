<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
$app = new \OCA\Files_external\Appinfo\Application();

$l = \OC::$server->getL10N('files_external');

OC::$CLASSPATH['OC\Files\Storage\StreamWrapper'] = 'files_external/lib/streamwrapper.php';
OC::$CLASSPATH['OC\Files\Storage\FTP'] = 'files_external/lib/ftp.php';
OC::$CLASSPATH['OC\Files\Storage\OwnCloud'] = 'files_external/lib/owncloud.php';
OC::$CLASSPATH['OC\Files\Storage\Google'] = 'files_external/lib/google.php';
OC::$CLASSPATH['OC\Files\Storage\Swift'] = 'files_external/lib/swift.php';
OC::$CLASSPATH['OC\Files\Storage\SMB'] = 'files_external/lib/smb.php';
OC::$CLASSPATH['OC\Files\Storage\SMB_OC'] = 'files_external/lib/smb_oc.php';
OC::$CLASSPATH['OC\Files\Storage\AmazonS3'] = 'files_external/lib/amazons3.php';
OC::$CLASSPATH['OC\Files\Storage\Dropbox'] = 'files_external/lib/dropbox.php';
OC::$CLASSPATH['OC\Files\Storage\SFTP'] = 'files_external/lib/sftp.php';
OC::$CLASSPATH['OC\Files\Storage\SFTP_Key'] = 'files_external/lib/sftp_key.php';
OC::$CLASSPATH['OC_Mount_Config'] = 'files_external/lib/config.php';
OC::$CLASSPATH['OCA\Files\External\Api'] = 'files_external/lib/api.php';

require_once __DIR__ . '/../3rdparty/autoload.php';

OCP\App::registerAdmin('files_external', 'settings');
if (OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes') == 'yes') {
	OCP\App::registerPersonal('files_external', 'personal');
}

\OCA\Files\App::getNavigationManager()->add([
	"id" => 'extstoragemounts',
	"appname" => 'files_external',
	"script" => 'list.php',
	"order" => 30,
	"name" => $l->t('External storage')
]);

// connecting hooks
OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\OC_Mount_Config', 'initMountPointsHook');
OCP\Util::connectHook('OC_User', 'post_login', 'OC\Files\Storage\SMB_OC', 'login');

OC_Mount_Config::registerBackend('\OC\Files\Storage\Local', [
	'backend' => (string)$l->t('Local'),
	'priority' => 150,
	'configuration' => [
		'datadir' => (string)$l->t('Location')
	],
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\AmazonS3', [
	'backend' => (string)$l->t('Amazon S3'),
	'priority' => 100,
	'configuration' => [
		'key' => (string)$l->t('Key'),
		'secret' => '*'.$l->t('Secret'),
		'bucket' => (string)$l->t('Bucket'),
	],
	'has_dependencies' => true,
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\AmazonS3', [
	'backend' => (string)$l->t('Amazon S3 and compliant'),
	'priority' => 100,
	'configuration' => [
		'key' => (string)$l->t('Access Key'),
		'secret' => '*'.$l->t('Secret Key'),
		'bucket' => (string)$l->t('Bucket'),
		'hostname' => '&'.$l->t('Hostname'),
		'port' => '&'.$l->t('Port'),
		'region' => '&'.$l->t('Region'),
		'use_ssl' => '!'.$l->t('Enable SSL'),
		'use_path_style' => '!'.$l->t('Enable Path Style')
	],
	'has_dependencies' => true,
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\Dropbox', [
	'backend' => 'Dropbox',
	'priority' => 100,
	'configuration' => [
		'configured' => '#configured',
		'app_key' => (string)$l->t('App key'),
		'app_secret' => '*'.$l->t('App secret'),
		'token' => '#token',
		'token_secret' => '#token_secret'
	],
	'custom' => 'dropbox',
	'has_dependencies' => true,
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\FTP', [
	'backend' => 'FTP',
	'priority' => 100,
	'configuration' => [
		'host' => (string)$l->t('Host'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Remote subfolder'),
		'secure' => '!'.$l->t('Secure ftps://')
	],
	'has_dependencies' => true,
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\Google', [
	'backend' => 'Google Drive',
	'priority' => 100,
	'configuration' => [
		'configured' => '#configured',
		'client_id' => (string)$l->t('Client ID'),
		'client_secret' => '*'.$l->t('Client secret'),
		'token' => '#token',
	],
	'custom' => 'google',
	'has_dependencies' => true,
]);


OC_Mount_Config::registerBackend('\OC\Files\Storage\Swift', [
	'backend' => (string)$l->t('OpenStack Object Storage'),
	'priority' => 100,
	'configuration' => [
		'user' => (string)$l->t('Username'),
		'bucket' => (string)$l->t('Bucket'),
		'region' => '&'.$l->t('Region (optional for OpenStack Object Storage)'),
		'key' => '&*'.$l->t('API Key (required for Rackspace Cloud Files)'),
		'tenant' => '&'.$l->t('Tenantname (required for OpenStack Object Storage)'),
		'password' => '&*'.$l->t('Password (required for OpenStack Object Storage)'),
		'service_name' => '&'.$l->t('Service Name (required for OpenStack Object Storage)'),
		'url' => '&'.$l->t('URL of identity endpoint (required for OpenStack Object Storage)'),
		'timeout' => '&'.$l->t('Timeout of HTTP requests in seconds'),
	],
	'has_dependencies' => true,
]);


if (!OC_Util::runningOnWindows()) {
	OC_Mount_Config::registerBackend('\OC\Files\Storage\SMB', [
		'backend' => 'SMB / CIFS',
		'priority' => 100,
		'configuration' => [
			'host' => (string)$l->t('Host'),
			'user' => (string)$l->t('Username'),
			'password' => '*'.$l->t('Password'),
			'share' => (string)$l->t('Share'),
			'root' => '&'.$l->t('Remote subfolder'),
		],
		'has_dependencies' => true,
	]);

	OC_Mount_Config::registerBackend('\OC\Files\Storage\SMB_OC', [
			'backend' => (string)$l->t('SMB / CIFS using OC login'),
			'priority' => 90,
			'configuration' => [
				'host' => (string)$l->t('Host'),
				'username_as_share' => '!'.$l->t('Username as share'),
				'share' => '&'.$l->t('Share'),
				'root' => '&'.$l->t('Remote subfolder'),
			],
		'has_dependencies' => true,
	]);
}

OC_Mount_Config::registerBackend('\OC\Files\Storage\DAV', [
	'backend' => 'WebDAV',
	'priority' => 100,
	'configuration' => [
		'host' => (string)$l->t('URL'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Remote subfolder'),
		'secure' => '!'.$l->t('Secure https://'),
	],
	'has_dependencies' => true,
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\OwnCloud', [
	'backend' => 'ownCloud',
	'priority' => 100,
	'configuration' => [
		'host' => (string)$l->t('URL'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Remote subfolder'),
		'secure' => '!'.$l->t('Secure https://'),
	],
]);


OC_Mount_Config::registerBackend('\OC\Files\Storage\SFTP', [
	'backend' => 'SFTP',
	'priority' => 100,
	'configuration' => [
		'host' => (string)$l->t('Host'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Remote subfolder'),
	],
]);

OC_Mount_Config::registerBackend('\OC\Files\Storage\SFTP_Key', [
	'backend' => 'SFTP with secret key login',
	'priority' => 100,
	'configuration' => array(
		'host' => (string)$l->t('Host'),
		'user' => (string)$l->t('Username'),
		'public_key' => (string)$l->t('Public key'),
		'private_key' => '#private_key',
		'root' => '&'.$l->t('Remote subfolder')),
	'custom' => 'sftp_key',
	]
);
$mountProvider = new \OCA\Files_External\Config\ConfigAdapter();
\OC::$server->getMountProviderCollection()->registerProvider($mountProvider);
