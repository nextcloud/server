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

class Test_StreamWrappers extends UnitTestCase {
	public function testFakeDir(){
		$items=array('foo','bar');
		OC_FakeDirStream::$dirs['test']=$items;
		$dh=opendir('fakedir://test');
		$result=array();
		while($file=readdir($dh)){
			$result[]=$file;
			$this->assertNotIdentical(false,array_search($file,$items));
		}
		$this->assertEqual(count($items),count($result));
	}

	public function testStaticStream(){
		$sourceFile=OC::$SERVERROOT.'/tests/data/lorem.txt';
		$staticFile='static://test';
		$this->assertFalse(file_exists($staticFile));
		file_put_contents($staticFile,file_get_contents($sourceFile));
		$this->assertTrue(file_exists($staticFile));
		$this->assertEqual(file_get_contents($sourceFile),file_get_contents($staticFile));
		unlink($staticFile);
		clearstatcache();
		$this->assertFalse(file_exists($staticFile));
	}
}