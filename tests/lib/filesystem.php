<?php
/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2012 Robin Appelman icewind@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

class Test_Filesystem extends UnitTestCase{
	/**
	 * @var array tmpDirs
	 */
	private $tmpDirs;

	/**
	 * @return array
	 */
	private function getStorageData(){
		$dir=OC_Helper::tmpFolder();
		$this->tmpDirs[]=$dir;
		return array('datadir'=>$dir);
	}

	public function tearDown(){
		foreach($this->tmpDirs as $dir){
			OC_Helper::rmdirr($dir);
		}
	}
	
	public function setUp(){
		OC_Filesystem::clearMounts();
	}

	public function testMount(){
		OC_Filesystem::mount('OC_Filestorage_Local',self::getStorageData(),'/');
		$this->assertEqual('/',OC_Filesystem::getMountPoint('/'));
		$this->assertEqual('/',OC_Filesystem::getMountPoint('/some/folder'));
		$this->assertEqual('',OC_Filesystem::getInternalPath('/'));
		$this->assertEqual('some/folder',OC_Filesystem::getInternalPath('/some/folder'));

		OC_Filesystem::mount('OC_Filestorage_Local',self::getStorageData(),'/some');
		$this->assertEqual('/',OC_Filesystem::getMountPoint('/'));
		$this->assertEqual('/some/',OC_Filesystem::getMountPoint('/some/folder'));
		$this->assertEqual('/some/',OC_Filesystem::getMountPoint('/some/'));
		$this->assertEqual('/',OC_Filesystem::getMountPoint('/some'));
		$this->assertEqual('folder',OC_Filesystem::getInternalPath('/some/folder'));
	}
}

?>