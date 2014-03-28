<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
$l = \OC_L10N::get('files_external');

OC::$CLASSPATH['OC\Files\Storage\StreamWrapper'] = 'files_external/lib/streamwrapper.php';
OC::$CLASSPATH['OC\Files\Storage\FTP'] = 'files_external/lib/ftp.php';
OC::$CLASSPATH['OC\Files\Storage\DAV'] = 'files_external/lib/webdav.php';
OC::$CLASSPATH['OC\Files\Storage\OwnCloud'] = 'files_external/lib/owncloud.php';
OC::$CLASSPATH['OC\Files\Storage\Google'] = 'files_external/lib/google.php';
OC::$CLASSPATH['OC\Files\Storage\Swift'] = 'files_external/lib/swift.php';
OC::$CLASSPATH['OC\Files\Storage\SMB'] = 'files_external/lib/smb.php';
OC::$CLASSPATH['OC\Files\Storage\SMB_OC'] = 'files_external/lib/smb_oc.php';
OC::$CLASSPATH['OC\Files\Storage\AmazonS3'] = 'files_external/lib/amazons3.php';
OC::$CLASSPATH['OC\Files\Storage\Dropbox'] = 'files_external/lib/dropbox.php';
OC::$CLASSPATH['OC\Files\Storage\SFTP'] = 'files_external/lib/sftp.php';
OC::$CLASSPATH['OC_Mount_Config'] = 'files_external/lib/config.php';

OCP\App::registerAdmin('files_external', 'settings');
if (OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes') == 'yes') {
	OCP\App::registerPersonal('files_external', 'personal');
}

// connecting hooks
OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\OC_Mount_Config', 'initMountPointsHook');
OCP\Util::connectHook('OC_User', 'post_login', 'OC\Files\Storage\SMB_OC', 'login');

OC_Mount_Config::registerBackend('\OC\Files\Storage\Local', array(
	'backend' => (string)$l->t('Local'),
	'priority' => 150,
	'configuration' => array(
		'datadir' => (string)$l->t('Location'))));

OC_Mount_Config::registerBackend('\OC\Files\Storage\AmazonS3', array(
	'backend' => (string)$l->t('Amazon S3'),
	'priority' => 100,
	'configuration' => array(
		'key' => (string)$l->t('Key'),
		'secret' => '*'.$l->t('Secret'),
		'bucket' => (string)$l->t('Bucket')),
	'has_dependencies' => true));

OC_Mount_Config::registerBackend('\OC\Files\Storage\AmazonS3', array(
	'backend' => (string)$l->t('Amazon S3 and compliant'),
	'priority' => 100,
	'configuration' => array(
		'key' => (string)$l->t('Access Key'),
		'secret' => '*'.$l->t('Secret Key'),
		'bucket' => (string)$l->t('Bucket'),
		'hostname' => '&'.$l->t('Hostname (optional)'),
		'port' => '&'.$l->t('Port (optional)'),
		'region' => '&'.$l->t('Region (optional)'),
		'use_ssl' => '!'.$l->t('Enable SSL'),
		'use_path_style' => '!'.$l->t('Enable Path Style')),
	'has_dependencies' => true));

OC_Mount_Config::registerBackend('\OC\Files\Storage\Dropbox', array(
	'backend' => 'Dropbox',
	'priority' => 100,
	'configuration' => array(
		'configured' => '#configured',
		'app_key' => (string)$l->t('App key'),
		'app_secret' => '*'.$l->t('App secret'),
		'token' => '#token',
		'token_secret' => '#token_secret'),
	'custom' => 'dropbox',
	'has_dependencies' => true));

OC_Mount_Config::registerBackend('\OC\Files\Storage\FTP', array(
	'backend' => 'FTP',
	'priority' => 100,
	'configuration' => array(
		'host' => (string)$l->t('Host'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Root'),
		'secure' => '!'.$l->t('Secure ftps://')),
	'has_dependencies' => true));

OC_Mount_Config::registerBackend('\OC\Files\Storage\Google', array(
	'backend' => 'Google Drive',
	'priority' => 100,
	'configuration' => array(
		'configured' => '#configured',
		'client_id' => (string)$l->t('Client ID'),
		'client_secret' => '*'.$l->t('Client secret'),
		'token' => '#token'),
	'custom' => 'google',
	'has_dependencies' => true));


OC_Mount_Config::registerBackend('\OC\Files\Storage\Swift', array(
	'backend' => (string)$l->t('OpenStack Object Storage'),
	'priority' => 100,
	'configuration' => array(
		'user' => (string)$l->t('Username (required)'),
		'bucket' => (string)$l->t('Bucket (required)'),
		'region' => '&'.$l->t('Region (optional for OpenStack Object Storage)'),
		'key' => '*'.$l->t('API Key (required for Rackspace Cloud Files)'),
		'tenant' => '&'.$l->t('Tenantname (required for OpenStack Object Storage)'),
		'password' => '*'.$l->t('Password (required for OpenStack Object Storage)'),
		'service_name' => '&'.$l->t('Service Name (required for OpenStack Object Storage)'),
		'url' => '&'.$l->t('URL of identity endpoint (required for OpenStack Object Storage)'),
		'timeout' => '&'.$l->t('Timeout of HTTP requests in seconds (optional)'),
	),
	'has_dependencies' => true));


if (!OC_Util::runningOnWindows()) {
	OC_Mount_Config::registerBackend('\OC\Files\Storage\SMB', array(
		'backend' => 'SMB / CIFS',
		'priority' => 100,
		'configuration' => array(
			'host' => (string)$l->t('Host'),
			'user' => (string)$l->t('Username'),
			'password' => '*'.$l->t('Password'),
			'share' => (string)$l->t('Share'),
			'root' => '&'.$l->t('Root')),
		'has_dependencies' => true));

	OC_Mount_Config::registerBackend('\OC\Files\Storage\SMB_OC', array(
			'backend' => (string)$l->t('SMB / CIFS using OC login'),
			'priority' => 90,
			'configuration' => array(
				'host' => (string)$l->t('Host'),
				'username_as_share' => '!'.$l->t('Username as share'),
				'share' => '&'.$l->t('Share'),
				'root' => '&'.$l->t('Root')),
		'has_dependencies' => true));
}

OC_Mount_Config::registerBackend('\OC\Files\Storage\DAV', array(
	'backend' => 'WebDAV',
	'priority' => 100,
	'configuration' => array(
		'host' => (string)$l->t('URL'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Root'),
		'secure' => '!'.$l->t('Secure https://')),
	'has_dependencies' => true));

OC_Mount_Config::registerBackend('\OC\Files\Storage\OwnCloud', array(
	'backend' => 'ownCloud',
	'priority' => 100,
	'configuration' => array(
		'host' => (string)$l->t('URL'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Remote subfolder'),
		'secure' => '!'.$l->t('Secure https://'))));


OC_Mount_Config::registerBackend('\OC\Files\Storage\SFTP', array(
	'backend' => 'SFTP',
	'priority' => 100,
	'configuration' => array(
		'host' => (string)$l->t('Host'),
		'user' => (string)$l->t('Username'),
		'password' => '*'.$l->t('Password'),
		'root' => '&'.$l->t('Root'))));
