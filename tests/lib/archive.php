<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

abstract class Test_Archive extends UnitTestCase {
	/**
	 * @var OC_Archive
	 */
	protected $instance;

	/**
	 * get the existing test archive
	 * @return OC_Archive
	 */
	abstract protected function getExisting();
	/**
	 * get a new archive for write testing
	 * @return OC_Archive
	 */
	abstract protected function getNew();
	
	public function testGetFiles(){
		$this->instance=$this->getExisting();
		$allFiles=$this->instance->getFiles();
		$expected=array('lorem.txt','logo-wide.png','dir/','dir/lorem.txt');
		$this->assertEqual(4,count($allFiles),'only found '.count($allFiles).' out of 4 expected files');
		foreach($expected as $file){
			$this->assertNotIdentical(false,array_search($file,$allFiles),'cant find '.$file.' in archive');
			$this->assertTrue($this->instance->fileExists($file),'file '.$file.' does not exist in archive');
		}
		$this->assertFalse($this->instance->fileExists('non/existing/file'));
		
		$rootContent=$this->instance->getFolder('');
		$expected=array('lorem.txt','logo-wide.png','dir/');
		$this->assertEqual(3,count($rootContent));
		foreach($expected as $file){
			$this->assertNotIdentical(false,array_search($file,$rootContent),'cant find '.$file.' in archive');
		}

		$dirContent=$this->instance->getFolder('dir/');
		$expected=array('lorem.txt');
		$this->assertEqual(1,count($dirContent));
		foreach($expected as $file){
			$this->assertNotIdentical(false,array_search($file,$dirContent),'cant find '.$file.' in archive');
		}
	}
	
	public function testContent(){
		$this->instance=$this->getExisting();
		$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
		$textFile=$dir.'/lorem.txt';
		$this->assertEqual(file_get_contents($textFile),$this->instance->getFile('lorem.txt'));
		
		$tmpFile=OCP\Files::tmpFile('.txt');
		$this->instance->extractFile('lorem.txt',$tmpFile);
		$this->assertEqual(file_get_contents($textFile),file_get_contents($tmpFile));
	}

	public function testWrite(){
		$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
		$textFile=$dir.'/lorem.txt';
		$this->instance=$this->getNew();
		$this->assertEqual(0,count($this->instance->getFiles()));
		$this->instance->addFile('lorem.txt',$textFile);
		$this->assertEqual(1,count($this->instance->getFiles()));
		$this->assertTrue($this->instance->fileExists('lorem.txt'));
		$this->assertFalse($this->instance->fileExists('lorem.txt/'));
		
		$this->assertEqual(file_get_contents($textFile),$this->instance->getFile('lorem.txt'));
		$this->instance->addFile('lorem.txt','foobar');
		$this->assertEqual('foobar',$this->instance->getFile('lorem.txt'));
	}

	public function testReadStream(){
		$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
		$this->instance=$this->getExisting();
		$fh=$this->instance->getStream('lorem.txt','r');
		$this->assertTrue($fh);
		$content=fread($fh,$this->instance->filesize('lorem.txt'));
		fclose($fh);
		$this->assertEqual(file_get_contents($dir.'/lorem.txt'),$content);
	}
	public function testWriteStream(){
		$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
		$this->instance=$this->getNew();
		$fh=$this->instance->getStream('lorem.txt','w');
		$source=fopen($dir.'/lorem.txt','r');
		OCP\Files::streamCopy($source,$fh);
		fclose($source);
		fclose($fh);
		$this->assertTrue($this->instance->fileExists('lorem.txt'));
		$this->assertEqual(file_get_contents($dir.'/lorem.txt'),$this->instance->getFile('lorem.txt'));
	}
	public function testFolder(){
		$this->instance=$this->getNew();
		$this->assertFalse($this->instance->fileExists('/test'));
		$this->assertFalse($this->instance->fileExists('/test/'));
		$this->instance->addFolder('/test');
		$this->assertTrue($this->instance->fileExists('/test'));
		$this->assertTrue($this->instance->fileExists('/test/'));
		$this->instance->remove('/test');
		$this->assertFalse($this->instance->fileExists('/test'));
		$this->assertFalse($this->instance->fileExists('/test/'));
	}
	public function testExtract(){
		$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
		$this->instance=$this->getExisting();
		$tmpDir=OCP\Files::tmpFolder();
		$this->instance->extract($tmpDir);
		$this->assertEqual(true,file_exists($tmpDir.'lorem.txt'));
		$this->assertEqual(true,file_exists($tmpDir.'dir/lorem.txt'));
		$this->assertEqual(true,file_exists($tmpDir.'logo-wide.png'));
		$this->assertEqual(file_get_contents($dir.'/lorem.txt'),file_get_contents($tmpDir.'lorem.txt'));
		OCP\Files::rmdirr($tmpDir);
	}
	public function testMoveRemove(){
		$dir=OC::$SERVERROOT.'/apps/files_archive/tests/data';
		$textFile=$dir.'/lorem.txt';
		$this->instance=$this->getNew();
		$this->instance->addFile('lorem.txt',$textFile);
		$this->assertFalse($this->instance->fileExists('target.txt'));
		$this->instance->rename('lorem.txt','target.txt');
		$this->assertTrue($this->instance->fileExists('target.txt'));
		$this->assertFalse($this->instance->fileExists('lorem.txt'));
		$this->assertEqual(file_get_contents($textFile),$this->instance->getFile('target.txt'));
		$this->instance->remove('target.txt');
		$this->assertFalse($this->instance->fileExists('target.txt'));
	}
}
