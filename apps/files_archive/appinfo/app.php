<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC::$CLASSPATH['OC_Archive'] = 'apps/files_archive/lib/archive.php';
foreach(array('ZIP') as $type){
	OC::$CLASSPATH['OC_Archive_'.$type] = 'apps/files_archive/lib/'.strtolower($type).'.php';
}

OC::$CLASSPATH['OC_Filestorage_Archive']='apps/files_archive/lib/storage.php';
