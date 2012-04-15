<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('archive.php');

if(is_dir(OC::$SERVERROOT.'/apps/files_archive/tests/data')){
	class Test_Archive_ZIP extends Test_Archive{
		protected function getExisting(){
			$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
			return new OC_Archive_ZIP($dir.'/data.zip');
		}

		protected function getNew(){
			return new OC_Archive_ZIP(OC_Helper::tmpFile('.zip'));
		}
	}
}else{
	abstract class Test_Archive_ZIP extends Test_Archive{}
}
