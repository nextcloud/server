<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_CryptStream extends UnitTestCase {
	private $tmpFiles=array();
	
	function testStream(){
		$stream=$this->getStream('test1','w');
		fwrite($stream,'foobar');
		fclose($stream);

		$stream=$this->getStream('test1','r');
		$data=fread($stream,6);
		fclose($stream);
		$this->assertEqual('foobar',$data);

		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
		$source=fopen($file,'r');
		$target=$this->getStream('test2','w');
		OC_Helper::streamCopy($source,$target);
		fclose($target);
		fclose($source);

		$stream=$this->getStream('test2','r');
		$data=stream_get_contents($stream);
		$original=file_get_contents($file);
		$this->assertEqual(strlen($original),strlen($data));
		$this->assertEqual($original,$data);
	}

	/**
	 * get a cryptstream to a temporary file
	 * @param string $id
	 * @param string $mode
	 * @return resource
	 */
	function getStream($id,$mode){
		if($id===''){
			$id=uniqid();
		}
		if(!isset($this->tmpFiles[$id])){
			$file=OC_Helper::tmpFile();
			$this->tmpFiles[$id]=$file;
		}else{
			$file=$this->tmpFiles[$id];
		}
		$stream=fopen($file,$mode);
		OC_CryptStream::$sourceStreams[$id]=array('path'=>'dummy','stream'=>$stream);
		return fopen('crypt://streams/'.$id,$mode);
	}
}
