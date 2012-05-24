<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 OC::$CLASSPATH['OC_FileStorage_StreamWrapper']='apps/files_external/lib/streamwrapper.php';
OC::$CLASSPATH['OC_Filestorage_FTP']='apps/files_external/lib/ftp.php';
OC::$CLASSPATH['OC_Filestorage_DAV']='apps/files_external/lib/webdav.php';
OC::$CLASSPATH['OC_Filestorage_Google']='apps/files_external/lib/google.php';
OC::$CLASSPATH['OC_Filestorage_SWIFT']='apps/files_external/lib/swift.php';
OC::$CLASSPATH['OC_Filestorage_SMB']='apps/files_external/lib/smb.php';
