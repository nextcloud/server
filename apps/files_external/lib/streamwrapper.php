<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


abstract class OC_FileStorage_StreamWrapper extends OC_Filestorage_Common{
	abstract public function constructUrl($path);

	public function mkdir($path){
		return mkdir($this->constructUrl($path));
	}

	public function rmdir($path){
		if($this->file_exists($path)){
			$succes=rmdir($this->constructUrl($path));
			clearstatcache();
			return $succes;
		}else{
			return false;
		}
	}

	public function opendir($path){
		return opendir($this->constructUrl($path));
	}

	public function filetype($path){
		return filetype($this->constructUrl($path));
	}

	public function is_readable($path){
		return true;//not properly supported
	}

	public function is_writable($path){
		return true;//not properly supported
	}

	public function file_exists($path){
		return file_exists($this->constructUrl($path));
	}

	public function unlink($path){
		$succes=unlink($this->constructUrl($path));
		clearstatcache();
		return $succes;
	}

	public function fopen($path,$mode){
		return fopen($this->constructUrl($path),$mode);
	}

	public function free_space($path){
		return 0;
	}

	public function touch($path,$mtime=null){
		if(is_null($mtime)){
			$fh=$this->fopen($path,'a');
			fwrite($fh,'');
			fclose($fh);
		}else{
			return false;//not supported
		}
	}

	public function getFile($path,$target){
		return copy($this->constructUrl($path),$target);
	}

	public function uploadFile($path,$target){
		return copy($path,$this->constructUrl($target));
	}

	public function rename($path1,$path2){
		return rename($this->constructUrl($path1),$this->constructUrl($path2));
	}

	public function stat($path){
		return stat($this->constructUrl($path));
	}



}
