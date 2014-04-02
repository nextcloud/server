<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

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
OC::$CLASSPATH['OC\Files\Storage\iRODS'] = 'files_external/lib/irods.php';
OC::$CLASSPATH['OC_Mount_Config'] = 'files_external/lib/config.php';

OCP\App::registerAdmin('files_external', 'settings');
if (OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes') == 'yes') {
	OCP\App::registerPersonal('files_external', 'personal');
}

// connecting hooks
OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\OC_Mount_Config', 'initMountPointsHook');
OCP\Util::connectHook('OC_User', 'post_login', 'OC\Files\Storage\iRODS', 'login');
OCP\Util::connectHook('OC_User', 'post_login', 'OC\Files\Storage\SMB_OC', 'login');

