<?php
class OC_FILEYSYSTEM_Test extends OC_TestCase
{
	public function setup(){
		OC_UTIL::setupFS('testuser','testcase');
	}
	public function tearDown(){
		OC_FILESYSTEM::chroot('');
		OC_FILESYSTEM::delTree('/testuser');
		OC_UTIL::tearDownFS();
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

	public function rmdir(){
		OC_FILESYSTEM::mkdir('/dummy');
		OC_FILESYSTEM::rmdir('/dummy');
		$this->assertEquals(false, OC_FILESYSTEM::file_exists('/dummy'),'Folder still exists after removing');
	}

	public function isFile(){
		$this->assertEquals(false, OC_FILESYSTEM::is_file('/'),'Root is a file');
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_FILESYSTEM::is_file('/dummy'),'Created file is not said to be a file');
	}

	public function opendir(){
		OC_FILESYSTEM::file_put_contents('/dummy1','foo');
		OC_FILESYSTEM::file_put_contents('/dummy2','foo');
		$dh=OC_FILESYSTEM::opendir('/');
		if(!$dh){
			$this->fail('Failed to open root');
		}
		$dummy1Found=false;
		$dummy2Found=false;
		while($file=readdir($dh)){
			if($file=='dummy1'){
				$dummy1Found=true;
			}elseif($file=='dummy2'){
				$dummy2Found=true;
			}elseif($file!='.' and $file!='..'){
				$this->fail('Unexpected filename when reading opened dir');
			}
		}
		$this->assertEquals(true,$dummy1Found,'Not all files found when reading opened dir');
		$this->assertEquals(true,$dummy2Found,'Not all files found when reading opened dir');
	}

	public function filesize(){
		OC_FILESYSTEM::file_put_contents('/dummy','1234567890');
		$this->assertEquals(10, OC_FILESYSTEM::filesize('/dummy'),'Unexpected filesize');
	}

	public function stat(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$time=time();
		$stat=OC_FILESYSTEM::stat('/dummy');
		$this->assertEquals(true,abs($time-$stat['atime'])<1,'Unexpected access time');//there can be small difference between those values due to running time
		$this->assertEquals(true,abs($time-$stat['ctime'])<1,'Unexpected creation time');
		$this->assertEquals(true,abs($time-$stat['mtime'])<1,'Unexpected modified time');
		$this->assertEquals(3,$stat['size'],'Unexpected filesize');
	}

	public function filetype(){
		OC_FILESYSTEM::file_put_contents('/dummyFile','foo');
		OC_FILESYSTEM::mkdir('/dummyFolder');
		$this->assertEquals('file', OC_FILESYSTEM::filetype('/dummyFile'),'Unexpected filetype of file');
		$this->assertEquals('dir', OC_FILESYSTEM::filetype('/dummyFolder'),'Unexpected filetype of folder');
	}

	public function readfile(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		ob_clean();
		ob_start();
		OC_FILESYSTEM::readfile('/dummy');
		$this->assertEquals('foo', ob_get_contents(),'Unexpected output of readfile');
		ob_end_clean();
	}

	public function isReadable(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_FILESYSTEM::is_readable('/dummy'),'Can\'t read created file');
	}

	public function isWritable(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_FILESYSTEM::is_writeable('/dummy'),'Can\'t write created file');
	}

	public function fileatime(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$time=time();
		$this->assertEquals(true,abs($time-OC_FILESYSTEM::fileatime('/dummy'))<1,'Unexpected access time');//there can be small difference between those values due to running time
	}

	public function filectime(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$time=time();
		$this->assertEquals(true,abs($time-OC_FILESYSTEM::filectime('/dummy'))<1,'Unexpected creation time');//there can be small difference between those values due to running time
	}

	public function filemtime(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$time=time();
		$this->assertEquals(true,abs($time-OC_FILESYSTEM::filemtime('/dummy'))<1,'Unexpected modified time');//there can be small difference between those values due to running time
	}

	public function fileGetContents(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals('foo', OC_FILESYSTEM::file_get_contents('/dummy'),'Unexpected content of file');
	}

	public function unlink(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		OC_FILESYSTEM::unlink('/dummy');
		$this->assertEquals(false, OC_FILESYSTEM::file_exists('/dummy'),'File still exists after deletion');
	}

	public function rename(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		OC_FILESYSTEM::rename('/dummy','/bar');
		$this->assertEquals(true, OC_FILESYSTEM::file_exists('/bar'),'New file doesnt exists after moving');
		$this->assertEquals(false, OC_FILESYSTEM::file_exists('/dummy'),'Old file still exists after moving');
		$this->assertEquals('foo', OC_FILESYSTEM::file_get_contents('/bar'),'Unexpected content of file after moving');
	}

	public function copy(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		OC_FILESYSTEM::copy('/dummy','/bar');
		$this->assertEquals(true, OC_FILESYSTEM::file_exists('/bar'),'New file doesnt exists after copying');
		$this->assertEquals(true, OC_FILESYSTEM::file_exists('/dummy'),'Old file doesnt exists after copying');
		$this->assertEquals('foo', OC_FILESYSTEM::file_get_contents('/bar'),'Unexpected content of file after copying');
	}

	public function fopen(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$fh=OC_FILESYSTEM::fopen('/dummy','r');
		if(!$fh){
			$this->fail('Cant open file for reading');
		}
		$content=fread($fh,3);
		$this->assertEquals('foo', $content,'Unexpected content of file');
		fclose($fh);
		$fh=OC_FILESYSTEM::fopen('/dummy','a');
		fwrite($fh,'bar',3);
		fclose($fh);
		$this->assertEquals('foobar', OC_FILESYSTEM::file_get_contents('/dummy'),'Unexpected content of file after appending');
		$fh=OC_FILESYSTEM::fopen('/dummy','w');
		fwrite($fh,'bar',3);
		fclose($fh);
		$this->assertEquals('bar', OC_FILESYSTEM::file_get_contents('/dummy'),'Unexpected content of file after writing');
	}

	public function toTmpFile(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$tmp=OC_FILESYSTEM::toTmpFile('/dummy');
		$this->assertEquals('foo', file_get_contents($tmp),'Unexpected content of temporary file');
		unlink($tmp);
	}

	public function fromTmpFile(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$tmp=OC_FILESYSTEM::toTmpFile('/dummy');
		OC_FILESYSTEM::fromTmpFile($tmp,'/bar');
		$this->assertEquals('foo', OC_FILESYSTEM::file_get_contents('/bar'),'Unexpected content of new file');
		$this->assertEquals(false, file_exists($tmp),'Temporary file still exists');
	}

	public function getMimeType(){
		OC_FILESYSTEM::file_put_contents('/dummy','some plain text');
		$this->assertEquals('text/plain', OC_FILESYSTEM::getMimeType('/dummy'),'Unexpected mimetype of pain text file');
		OC_FILESYSTEM::file_put_contents('/dummy',"<?xml version='1.0'?>\n</dummy>");
		$this->assertEquals('application/xml', OC_FILESYSTEM::getMimeType('/dummy'),'Unexpected mimetype of xml file');
	}

	public function delTree(){
		OC_FILESYSTEM::mkdir('/dummy');
		OC_FILESYSTEM::file_put_contents('/dummy/bar','foo');
		OC_FILESYSTEM::delTree('/dummy');
		$this->assertEquals(false, OC_FILESYSTEM::file_exists('/dummy/bar'),'File in deleted folder still exists');
		$this->assertEquals(false, OC_FILESYSTEM::file_exists('/dummy'),'Deleted folder still exists');
	}

	public function getTree(){
		OC_FILESYSTEM::mkdir('/dummy');
		OC_FILESYSTEM::file_put_contents('/dummy/bar','foo');
		$expected=array('/dummy','/dummy/bar');
		$this->assertEquals($expected, OC_FILESYSTEM::getTree('/dummy'),'Unexpected filelist returned');
	}

	public function hash(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals(md5('foo'), OC_FILESYSTEM::hash('md5','/dummy'),'Unexpected md5 hash of file');
	}

	public function freeSpace(){
		$oldSpace=OC_FILESYSTEM::free_space('/');
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$newSpace=OC_FILESYSTEM::free_space('/');
		$this->assertEquals(true, $newSpace<$oldSpace,'free space not smaller after creating a non empty file');
	}

	public function search(){
		OC_FILESYSTEM::file_put_contents('/dummy','foo');
		$this->assertEquals(array('/dummy'),OC_FILESYSTEM::search('my'),'unexpected file list after search');
	}
}
return 'OC_FILEYSYSTEM_Test';
?>