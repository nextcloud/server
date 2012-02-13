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

abstract class Test_FileStorage extends UnitTestCase {
	/**
	 * @var OC_Filestorage instance
	 */
	protected $instance;

	/**
	 * the root folder of the storage should always exist, be readable and be recognized as a directory
	 */
	public function testRoot(){
		$this->assertTrue($this->instance->file_exists('/'),'Root folder does not exist');
		$this->assertTrue($this->instance->is_readable('/'),'Root folder is not readable');
		$this->assertTrue($this->instance->is_dir('/'),'Root folder is not a directory');
		$this->assertFalse($this->instance->is_file('/'),'Root folder is a file');
		
		//without this, any further testing would be useless, not an acutal requirement for filestorage though
		$this->assertTrue($this->instance->is_writable('/'),'Root folder is not writable');
	}

	/**
	 * test the various uses of file_get_contents and file_put_contents
	 */
	public function testGetPutContents(){
		$sourceFile=OC::$SERVERROOT.'/tests/data/lorem.txt';
		$sourceText=file_get_contents($sourceFile);
		
		//fill a file with string data
		$this->instance->file_put_contents('/lorem.txt',$sourceText);
		$this->assertEqual($sourceText,$this->instance->file_get_contents('/lorem.txt'),'data returned from file_get_contents is not equal to the source data');

		//fill a file with a stream
		$this->instance->file_put_contents('/lorem.txt',fopen($sourceFile,'r'));
		$this->assertEqual($sourceText,$this->instance->file_get_contents('/lorem.txt'),'data returned from file_get_contents is not equal to the source data');

		//empty the file
		$this->instance->file_put_contents('/lorem.txt','');
		$this->assertEqual('',$this->instance->file_get_contents('/lorem.txt'),'file not emptied');
	}
}


