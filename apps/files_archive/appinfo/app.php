<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC::$CLASSPATH['OC_Filestorage_Archive']='apps/files_archive/lib/storage.php';
OC::$CLASSPATH['OC_Files_Versions_Hooks_Handler'] = 'files_versions/lib/hooks_handler.php';

OCP\Util::connectHook('OC_Filesystem','get_mountpoint','OC_Filestorage_Archive','autoMount');
OCP\Util::connectHook('OC_Filesystem', 'delete', "OC_Files_Versions_Hooks_Handler", "removeVersions");

OCP\Util::addscript( 'files_archive', 'archive' );
