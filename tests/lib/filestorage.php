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
		$this->assertEqual('dir',$this->instance->filetype('/'));
		
		//without this, any further testing would be useless, not an acutal requirement for filestorage though
		$this->assertTrue($this->instance->is_writable('/'),'Root folder is not writable');
	}
	
	public function testDirectories(){
		$this->assertFalse($this->instance->file_exists('/folder'));
		
		$this->assertTrue($this->instance->mkdir('/folder'));
		
		$this->assertTrue($this->instance->file_exists('/folder'));
		$this->assertTrue($this->instance->is_dir('/folder'));
		$this->assertFalse($this->instance->is_file('/folder'));
		$this->assertEqual('dir',$this->instance->filetype('/folder'));
		$this->assertEqual(0,$this->instance->filesize('/folder'));
		$this->assertTrue($this->instance->is_readable('/folder'));
		$this->assertTrue($this->instance->is_writable('/folder'));
		
		$dh=$this->instance->opendir('/');
		$content=array();
		while($file=readdir($dh)){
			if($file!='.' and $file!='..'){
				$content[]=$file;
			}
		}
		$this->assertEqual(array('folder'),$content);
		
		$this->assertFalse($this->instance->mkdir('/folder'));//cant create existing folders
		$this->assertTrue($this->instance->rmdir('/folder'));
		
		$this->assertFalse($this->instance->file_exists('/folder'));
		
		$this->assertFalse($this->instance->rmdir('/folder'));//cant remove non existing folders
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
	
	/**
	 * test various known mimetypes
	 */
	public function testMimeType(){
		$this->assertEqual('httpd/unix-directory',$this->instance->getMimeType('/'));
		$this->assertEqual(false,$this->instance->getMimeType('/non/existing/file'));
		
		$textFile=OC::$SERVERROOT.'/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt',fopen($textFile,'r'));
		$this->assertEqual('text/plain',$this->instance->getMimeType('/lorem.txt'));
		
		$pngFile=OC::$SERVERROOT.'/tests/data/logo-wide.png';
		$this->instance->file_put_contents('/logo-wide.png',fopen($pngFile,'r'));
		$this->assertEqual('image/png',$this->instance->getMimeType('/logo-wide.png'));
		
		$svgFile=OC::$SERVERROOT.'/tests/data/logo-wide.svg';
		$this->instance->file_put_contents('/logo-wide.svg',fopen($svgFile,'r'));
		$this->assertEqual('image/svg+xml',$this->instance->getMimeType('/logo-wide.svg'));
	}
}


