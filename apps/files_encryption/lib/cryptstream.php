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
 */

class OC_CryptStream{
	private $source;

	public function stream_open($path, $mode, $options, &$opened_path){
		$path=str_replace('crypt://','',$path);
		$this->source=OC_FileSystem::fopen($path.'.enc',$mode);
		if(!is_resource($this->source)){
			OC_Log::write('files_encryption','failed to open '.$path.'.enc',OC_Log::ERROR);
		}
		return is_resource($this->source);
	}
	
	public function stream_seek($offset, $whence=SEEK_SET){
		fseek($this->source,$offset,$whence);
	}
	
	public function stream_tell(){
		return ftell($this->source);
	}
	
	public function stream_read($count){
		$pos=0;
		$currentPos=ftell($this->source);
		$offset=$currentPos%8192;
		fseek($this->source,-$offset,SEEK_CUR);
		$result='';
		while($count>$pos){
			$data=fread($this->source,8192);
			$pos+=8192;
			$result.=OC_Crypt::decrypt($data);
		}
		return substr($result,$offset,$count);
	}
	
	public function stream_write($data){
		$length=strlen($data);
		$written=0;
		$currentPos=ftell($this->source);
		if($currentPos%8192!=0){
			//make sure we always start on a block start
			fseek($this->source,-($currentPos%8192),SEEK_CUR);
			$encryptedBlock=fread($this->source,8192);
			fseek($this->source,-($currentPos%8192),SEEK_CUR);
			$block=OC_Crypt::decrypt($encryptedBlock);
			$data=substr($block,0,$currentPos%8192).$data;
		}
		while(strlen($data)>0){
			if(strlen($data)<8192){
				//fetch the current data in that block and append it to the input so we always write entire blocks
				$oldPos=ftell($this->source);
				$encryptedBlock=fread($this->source,8192);
				fseek($this->source,$oldPos);
				$block=OC_Crypt::decrypt($encryptedBlock);
				$data.=substr($block,strlen($data));
			}
			$encrypted=OC_Crypt::encrypt(substr($data,0,8192));
			fwrite($this->source,$encrypted);
			$data=substr($data,8192);
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

	public function stream_close(){
		return fclose($this->source);
	}
}