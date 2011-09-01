<?php
class OC_FILEYSYSTEM_Test extends OC_TestCase
{
	public function setup(){
		OC_Util::setupFS('testuser','testcase');
	}
	public function tearDown(){
		OC_Filesystem::chroot('');
		OC_Filesystem::delTree('/testuser');
		OC_Util::tearDownFS();
	}
	
	public function isDir(){
		$this->assertEquals(true, OC_Filesystem::is_dir('/'),'Root is not a directory');
	}
	
	public function fileExists(){
		$this->assertEquals(false, OC_Filesystem::file_exists('/dummy'),'Unexpected result with non-existing file');
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_Filesystem::file_exists('/dummy'),'Unexpected result with existing file');
	}

	public function mkdir(){
		OC_Filesystem::mkdir('/dummy');
		$this->assertEquals(true, OC_Filesystem::file_exists('/dummy'),'No such file or directory after creating folder');
		$this->assertEquals(true, OC_Filesystem::is_dir('/dummy'),'File created instead of filder');
	}

	public function rmdir(){
		OC_Filesystem::mkdir('/dummy');
		OC_Filesystem::rmdir('/dummy');
		$this->assertEquals(false, OC_Filesystem::file_exists('/dummy'),'Folder still exists after removing');
	}

	public function isFile(){
		$this->assertEquals(false, OC_Filesystem::is_file('/'),'Root is a file');
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_Filesystem::is_file('/dummy'),'Created file is not said to be a file');
	}

	public function opendir(){
		OC_Filesystem::file_put_contents('/dummy1','foo');
		OC_Filesystem::file_put_contents('/dummy2','foo');
		$dh=OC_Filesystem::opendir('/');
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
		OC_Filesystem::file_put_contents('/dummy','1234567890');
		$this->assertEquals(10, OC_Filesystem::filesize('/dummy'),'Unexpected filesize');
	}

	public function stat(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$time=time();
		$stat=OC_Filesystem::stat('/dummy');
		$this->assertEquals(true,abs($time-$stat['atime'])<1,'Unexpected access time');//there can be small difference between those values due to running time
		$this->assertEquals(true,abs($time-$stat['ctime'])<1,'Unexpected creation time');
		$this->assertEquals(true,abs($time-$stat['mtime'])<1,'Unexpected modified time');
		$this->assertEquals(3,$stat['size'],'Unexpected filesize');
	}

	public function filetype(){
		OC_Filesystem::file_put_contents('/dummyFile','foo');
		OC_Filesystem::mkdir('/dummyFolder');
		$this->assertEquals('file', OC_Filesystem::filetype('/dummyFile'),'Unexpected filetype of file');
		$this->assertEquals('dir', OC_Filesystem::filetype('/dummyFolder'),'Unexpected filetype of folder');
	}

	public function readfile(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		ob_start();
		OC_Filesystem::readfile('/dummy');
		$this->assertEquals('foo', ob_get_contents(),'Unexpected output of readfile');
		ob_end_clean();
	}

	public function isReadable(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_Filesystem::is_readable('/dummy'),'Can\'t read created file');
	}

	public function isWritable(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals(true, OC_Filesystem::is_writeable('/dummy'),'Can\'t write created file');
	}

	public function fileatime(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$time=time();
		$this->assertEquals(true,abs($time-OC_Filesystem::fileatime('/dummy'))<1,'Unexpected access time');//there can be small difference between those values due to running time
	}

	public function filectime(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$time=time();
		$this->assertEquals(true,abs($time-OC_Filesystem::filectime('/dummy'))<1,'Unexpected creation time');//there can be small difference between those values due to running time
	}

	public function filemtime(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$time=time();
		$this->assertEquals(true,abs($time-OC_Filesystem::filemtime('/dummy'))<1,'Unexpected modified time');//there can be small difference between those values due to running time
	}

	public function fileGetContents(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals('foo', OC_Filesystem::file_get_contents('/dummy'),'Unexpected content of file');
	}

	public function unlink(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		OC_Filesystem::unlink('/dummy');
		$this->assertEquals(false, OC_Filesystem::file_exists('/dummy'),'File still exists after deletion');
	}

	public function rename(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		OC_Filesystem::rename('/dummy','/bar');
		$this->assertEquals(true, OC_Filesystem::file_exists('/bar'),'New file doesnt exists after moving');
		$this->assertEquals(false, OC_Filesystem::file_exists('/dummy'),'Old file still exists after moving');
		$this->assertEquals('foo', OC_Filesystem::file_get_contents('/bar'),'Unexpected content of file after moving');
	}

	public function copy(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		OC_Filesystem::copy('/dummy','/bar');
		$this->assertEquals(true, OC_Filesystem::file_exists('/bar'),'New file doesnt exists after copying');
		$this->assertEquals(true, OC_Filesystem::file_exists('/dummy'),'Old file doesnt exists after copying');
		$this->assertEquals('foo', OC_Filesystem::file_get_contents('/bar'),'Unexpected content of file after copying');
	}

	public function fopen(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$fh=OC_Filesystem::fopen('/dummy','r');
		if(!$fh){
			$this->fail('Cant open file for reading');
		}
		$content=fread($fh,3);
		$this->assertEquals('foo', $content,'Unexpected content of file');
		fclose($fh);
		$fh=OC_Filesystem::fopen('/dummy','a');
		fwrite($fh,'bar',3);
		fclose($fh);
		$this->assertEquals('foobar', OC_Filesystem::file_get_contents('/dummy'),'Unexpected content of file after appending');
		$fh=OC_Filesystem::fopen('/dummy','w');
		fwrite($fh,'bar',3);
		fclose($fh);
		$this->assertEquals('bar', OC_Filesystem::file_get_contents('/dummy'),'Unexpected content of file after writing');
	}

	public function toTmpFile(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$tmp=OC_Filesystem::toTmpFile('/dummy');
		$this->assertEquals('foo', file_get_contents($tmp),'Unexpected content of temporary file');
		unlink($tmp);
	}

	public function fromTmpFile(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$tmp=OC_Filesystem::toTmpFile('/dummy');
		OC_Filesystem::fromTmpFile($tmp,'/bar');
		$this->assertEquals('foo', OC_Filesystem::file_get_contents('/bar'),'Unexpected content of new file');
		$this->assertEquals(false, file_exists($tmp),'Temporary file still exists');
	}

	public function getMimeType(){
		OC_Filesystem::file_put_contents('/dummy','some plain text');
		$this->assertEquals('text/plain', OC_Filesystem::getMimeType('/dummy'),'Unexpected mimetype of pain text file');
		OC_Filesystem::file_put_contents('/dummy',"<?xml version='1.0'?>\n</dummy>");
		$this->assertEquals('application/xml', OC_Filesystem::getMimeType('/dummy'),'Unexpected mimetype of xml file');
	}

	public function delTree(){
		OC_Filesystem::mkdir('/dummy');
		OC_Filesystem::file_put_contents('/dummy/bar','foo');
		OC_Filesystem::delTree('/dummy');
		$this->assertEquals(false, OC_Filesystem::file_exists('/dummy/bar'),'File in deleted folder still exists');
		$this->assertEquals(false, OC_Filesystem::file_exists('/dummy'),'Deleted folder still exists');
	}

	public function getTree(){
		OC_Filesystem::mkdir('/dummy');
		OC_Filesystem::file_put_contents('/dummy/bar','foo');
		$expected=array('/dummy','/dummy/bar');
		$this->assertEquals($expected, OC_Filesystem::getTree('/dummy'),'Unexpected filelist returned');
	}

	public function hash(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals(md5('foo'), OC_Filesystem::hash('md5','/dummy'),'Unexpected md5 hash of file');
	}

	public function freeSpace(){
		$oldSpace=OC_Filesystem::free_space('/');
		OC_Filesystem::file_put_contents('/dummy','foo');
		$newSpace=OC_Filesystem::free_space('/');
		$this->assertEquals(true, $newSpace<$oldSpace,'free space not smaller after creating a non empty file');
	}

	public function search(){
		OC_Filesystem::file_put_contents('/dummy','foo');
		$this->assertEquals(array('/dummy'),OC_Filesystem::search('my'),'unexpected file list after search');
	}
}
return 'OC_FILEYSYSTEM_Test';
?>