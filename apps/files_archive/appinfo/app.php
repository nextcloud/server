<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC::$CLASSPATH['OC_Filestorage_Archive']='apps/files_archive/lib/storage.php';

OCP\Util::connectHook('OC_Filesystem','get_mountpoint','OC_Filestorage_Archive','autoMount');

OCP\Util::addscript( 'files_archive', 'archive' );
