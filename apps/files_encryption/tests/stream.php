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
		$stream=$this->getStream('test1','w',strlen('foobar'));
		fwrite($stream,'foobar');
		fclose($stream);

		$stream=$this->getStream('test1','r',strlen('foobar'));
		$data=fread($stream,6);
		fclose($stream);
		$this->assertEqual('foobar',$data);

		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
		$source=fopen($file,'r');
		$target=$this->getStream('test2','w',0);
		OCP\Files::streamCopy($source,$target);
		fclose($target);
		fclose($source);

		$stream=$this->getStream('test2','r',filesize($file));
		$data=stream_get_contents($stream);
		$original=file_get_contents($file);
		$this->assertEqual(strlen($original),strlen($data));
		$this->assertEqual($original,$data);
	}

	/**
	 * get a cryptstream to a temporary file
	 * @param string $id
	 * @param string $mode
	 * @param int size
	 * @return resource
	 */
	function getStream($id,$mode,$size){
		if($id===''){
			$id=uniqid();
		}
		if(!isset($this->tmpFiles[$id])){
			$file=OCP\Files::tmpFile();
			$this->tmpFiles[$id]=$file;
		}else{
			$file=$this->tmpFiles[$id];
		}
		$stream=fopen($file,$mode);
		OC_CryptStream::$sourceStreams[$id]=array('path'=>'dummy'.$id,'stream'=>$stream,'size'=>$size);
		return fopen('crypt://streams/'.$id,$mode);
	}

	function testBinary(){
		$file=__DIR__.'/binary';
		$source=file_get_contents($file);

		$stream=$this->getStream('test','w',strlen($source));
		fwrite($stream,$source);
		fclose($stream);

		$stream=$this->getStream('test','r',strlen($source));
		$data=stream_get_contents($stream);
		fclose($stream);
		$this->assertEqual(strlen($data),strlen($source));
		$this->assertEqual($source,$data);

		$file=__DIR__.'/zeros';
		$source=file_get_contents($file);

		$stream=$this->getStream('test2','w',strlen($source));
		fwrite($stream,$source);
		fclose($stream);

		$stream=$this->getStream('test2','r',strlen($source));
		$data=stream_get_contents($stream);
		fclose($stream);
		$this->assertEqual(strlen($data),strlen($source));
		$this->assertEqual($source,$data);
	}
}
