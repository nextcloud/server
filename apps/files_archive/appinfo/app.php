<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC::$CLASSPATH['OC_Archive'] = 'apps/files_archive/lib/archive.php';
OC::$CLASSPATH['Archive_Tar'] = '3rdparty/Archive/Tar.php';
foreach(array('ZIP','TAR') as $type){
	OC::$CLASSPATH['OC_Archive_'.$type] = 'apps/files_archive/lib/'.strtolower($type).'.php';
}

OC::$CLASSPATH['OC_Filestorage_Archive']='apps/files_archive/lib/storage.php';

OC_Hook::connect('OC_Filesystem','get_mountpoint','OC_Filestorage_Archive','autoMount');

OCP\Util::addscript( 'files_archive', 'archive' );
