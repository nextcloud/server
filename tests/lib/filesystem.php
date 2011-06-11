<?php
class OC_FILEYSYSTEM_Test extends OC_TestCase
{
	public function setup(){
		OC_UTIL::setupFS('testuser','testcase');
	}
	
	public function isDir(){
		$this->assertEquals(true, OC_FILESYSTEM::is_dir('/'),'Root is not a directory');
	}
	
	public function fileExists(){
		$this->assertEquals(false, OC_FILESYSTEM::file_exists('/dummy'),'Unexpected result with non-existing file');
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_FILESYSTEM::file_exists('/dummy'),'Unexpected result with existing file');
	}

	public function mkdir(){
		OC_FILESYSTEM::mkdir('/dummy');
		$this->assertEquals(true, OC_FILESYSTEM::file_exists('/dummy'),'No such file or directory after creating folder');
		$this->assertEquals(true, OC_FILESYSTEM::is_dir('/dummy'),'File created instead of filder');
	}
	
	public function tearDown(){
		OC_FILESYSTEM::chroot('');
		OC_FILESYSTEM::delTree('/testuser');
		OC_UTIL::tearDownFS();
	}
}
return 'OC_FILEYSYSTEM_Test';
?>