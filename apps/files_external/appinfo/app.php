<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC::$CLASSPATH['OC\Files\Storage\StreamWrapper']='apps/files_external/lib/streamwrapper.php';
OC::$CLASSPATH['OC\Files\Storage\FTP']='apps/files_external/lib/ftp.php';
OC::$CLASSPATH['OC\Files\Storage\DAV']='apps/files_external/lib/webdav.php';
OC::$CLASSPATH['OC\Files\Storage\Google']='apps/files_external/lib/google.php';
OC::$CLASSPATH['OC\Files\Storage\SWIFT']='apps/files_external/lib/swift.php';
OC::$CLASSPATH['OC\Files\Storage\SMB']='apps/files_external/lib/smb.php';
OC::$CLASSPATH['OC\Files\Storage\AmazonS3']='apps/files_external/lib/amazons3.php';
OC::$CLASSPATH['OC\Files\Storage\Dropbox']='apps/files_external/lib/dropbox.php';
OC::$CLASSPATH['OC_Mount_Config']='apps/files_external/lib/config.php';

OCP\App::registerAdmin('files_external', 'settings');
if (OCP\Config::getAppValue('files_external', 'allow_user_mounting', 'yes') == 'yes') {
	OCP\App::registerPersonal('files_external', 'personal');
}
