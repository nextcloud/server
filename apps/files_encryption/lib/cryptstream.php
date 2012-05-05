<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2011 Robin Appelman icewind1991@gmail.com
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

/**
 * transparently encrypted filestream
 *
 * you can use it as wrapper around an existing stream by setting OC_CryptStream::$sourceStreams['foo']=array('path'=>$path,'stream'=>$stream)
 *   and then fopen('crypt://streams/foo');
 */

class OC_CryptStream{
	public static $sourceStreams=array();
	private $source;
	private $path;
	private $readBuffer;//for streams that dont support seeking
	private $meta=array();//header/meta for source stream
	private $count;
	private $writeCache;

	public function stream_open($path, $mode, $options, &$opened_path){
		$path=str_replace('crypt://','',$path);
		if(dirname($path)=='streams' and isset(self::$sourceStreams[basename($path)])){
			$this->source=self::$sourceStreams[basename($path)]['stream'];
			$this->path=self::$sourceStreams[basename($path)]['path'];
		}else{
			$this->path=$path;
			OCP\Util::writeLog('files_encryption','open encrypted '.$path. ' in '.$mode,OCP\Util::DEBUG);
			OC_FileProxy::$enabled=false;//disable fileproxies so we can open the source file
			$this->source=OC_FileSystem::fopen($path,$mode);
			OC_FileProxy::$enabled=true;
			if(!is_resource($this->source)){
				OCP\Util::writeLog('files_encryption','failed to open '.$path,OCP\Util::ERROR);
			}
		}
		if(is_resource($this->source)){
			$this->meta=stream_get_meta_data($this->source);
		}
		return is_resource($this->source);
	}
	
	public function stream_seek($offset, $whence=SEEK_SET){
		$this->flush();
		fseek($this->source,$offset,$whence);
	}
	
	public function stream_tell(){
		return ftell($this->source);
	}
	
	public function stream_read($count){
		//$count will always be 8192 https://bugs.php.net/bug.php?id=21641
		//This makes this function a lot simpler but will breake everything the moment it's fixed
		$this->writeCache='';
		if($count!=8192){
			OCP\Util::writeLog('files_encryption','php bug 21641 no longer holds, decryption will not work',OCP\Util::FATAL);
			die();
		}
		$data=fread($this->source,8192);
		if(strlen($data)){
			$result=OC_Crypt::decrypt($data);
		}else{
			$result='';
		}
		return $result;
	}
	
	public function stream_write($data){
		$length=strlen($data);
		$written=0;
		$currentPos=ftell($this->source);
		if($this->writeCache){
			$data=$this->writeCache.$data;
			$this->writeCache='';
		}
		if($currentPos%8192!=0){
			//make sure we always start on a block start
			fseek($this->source,-($currentPos%8192),SEEK_CUR);
			$encryptedBlock=fread($this->source,8192);
			fseek($this->source,-($currentPos%8192),SEEK_CUR);
			$block=OC_Crypt::decrypt($encryptedBlock);
			$data=substr($block,0,$currentPos%8192).$data;
			fseek($this->source,-($currentPos%8192),SEEK_CUR);
		}
		while(strlen($data)>0){
			if(strlen($data)<8192){
				$this->writeCache=$data;
				$data='';
			}else{
				$encrypted=OC_Crypt::encrypt(substr($data,0,8192));
				fwrite($this->source,$encrypted);
				$data=substr($data,8192);
			}
		}
		return $length;
	}

	public function stream_set_option($option,$arg1,$arg2){
		switch($option){
			case STREAM_OPTION_BLOCKING:
				stream_set_blocking($this->source,$arg1);
				break;
			case STREAM_OPTION_READ_TIMEOUT:
				stream_set_timeout($this->source,$arg1,$arg2);
				break;
			case STREAM_OPTION_WRITE_BUFFER:
				stream_set_write_buffer($this->source,$arg1,$arg2);
		}
	}

	public function stream_stat(){
		return fstat($this->source);
	}
	
	public function stream_lock($mode){
		flock($this->source,$mode);
	}
	
	public function stream_flush(){
		return fflush($this->source);
	}

	public function stream_eof(){
		return feof($this->source);
	}

	private function flush(){
		if($this->writeCache){
			$encrypted=OC_Crypt::encrypt($this->writeCache);
			fwrite($this->source,$encrypted);
			$this->writeCache='';
		}
	}

	public function stream_close(){
		$this->flush();
		if($this->meta['mode']!='r' and $this->meta['mode']!='rb'){
			OC_FileCache::put($this->path,array('encrypted'=>true));
		}
		return fclose($this->source);
	}
}
