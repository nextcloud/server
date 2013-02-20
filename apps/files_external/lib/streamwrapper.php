<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

abstract class StreamWrapper extends \OC\Files\Storage\Common{
	private $ready = false;

	protected function init(){
		if($this->ready) {
			return;
		}
		$this->ready = true;

		//create the root folder if necesary
		if(!$this->is_dir('')) {
			$this->mkdir('');
		}
	}

	abstract public function constructUrl($path);

	public function mkdir($path) {
		$this->init();
		return mkdir($this->constructUrl($path));
	}

	public function rmdir($path) {
		$this->init();
		if($this->file_exists($path)) {
			$succes = rmdir($this->constructUrl($path));
			clearstatcache();
			return $succes;
		} else {
			return false;
		}
	}

	public function opendir($path) {
		$this->init();
		return opendir($this->constructUrl($path));
	}

	public function filetype($path) {
		$this->init();
		return filetype($this->constructUrl($path));
	}

	public function isReadable($path) {
		return true;//not properly supported
	}

	public function isUpdatable($path) {
		return true;//not properly supported
	}

	public function file_exists($path) {
		$this->init();
		return file_exists($this->constructUrl($path));
	}

	public function unlink($path) {
		$this->init();
		$succes = unlink($this->constructUrl($path));
		clearstatcache();
		return $succes;
	}

	public function fopen($path, $mode) {
		$this->init();
		return fopen($this->constructUrl($path), $mode);
	}

	public function touch($path, $mtime=null) {
		$this->init();
		if(is_null($mtime)) {
			$fh = $this->fopen($path, 'a');
			fwrite($fh, '');
			fclose($fh);
		} else {
			return false;//not supported
		}
	}

	public function getFile($path, $target) {
		$this->init();
		return copy($this->constructUrl($path), $target);
	}

	public function uploadFile($path, $target) {
		$this->init();
		return copy($path, $this->constructUrl($target));
	}

	public function rename($path1, $path2) {
		$this->init();
		return rename($this->constructUrl($path1), $this->constructUrl($path2));
	}

	public function stat($path) {
		$this->init();
		return stat($this->constructUrl($path));
	}

}
