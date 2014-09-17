<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

abstract class StreamWrapper extends Common {

	/**
	 * @param string $path
	 * @return string|null
	 */
	abstract public function constructUrl($path);

	public function mkdir($path) {
		return mkdir($this->constructUrl($path));
	}

	public function rmdir($path) {
		if ($this->file_exists($path) && $this->isDeletable($path)) {
			$dh = $this->opendir($path);
			while (($file = readdir($dh)) !== false) {
				if ($this->is_dir($path . '/' . $file)) {
					$this->rmdir($path . '/' . $file);
				} else {
					$this->unlink($path . '/' . $file);
				}
			}
			$url = $this->constructUrl($path);
			$success = rmdir($url);
			clearstatcache(false, $url);
			return $success;
		} else {
			return false;
		}
	}

	public function opendir($path) {
		return opendir($this->constructUrl($path));
	}

	public function filetype($path) {
		return @filetype($this->constructUrl($path));
	}

	public function file_exists($path) {
		return file_exists($this->constructUrl($path));
	}

	public function unlink($path) {
		$url = $this->constructUrl($path);
		$success = unlink($url);
		// normally unlink() is supposed to do this implicitly,
		// but doing it anyway just to be sure
		clearstatcache(false, $url);
		return $success;
	}

	public function fopen($path, $mode) {
		return fopen($this->constructUrl($path), $mode);
	}

	public function touch($path, $mtime = null) {
		if ($this->file_exists($path)) {
			if (is_null($mtime)) {
				$fh = $this->fopen($path, 'a');
				fwrite($fh, '');
				fclose($fh);

				return true;
			} else {
				return false; //not supported
			}
		} else {
			$this->file_put_contents($path, '');
			return true;
		}
	}

	/**
	 * @param string $path
	 * @param string $target
	 */
	public function getFile($path, $target) {
		return copy($this->constructUrl($path), $target);
	}

	/**
	 * @param string $target
	 */
	public function uploadFile($path, $target) {
		return copy($path, $this->constructUrl($target));
	}

	public function rename($path1, $path2) {
		return rename($this->constructUrl($path1), $this->constructUrl($path2));
	}

	public function stat($path) {
		return stat($this->constructUrl($path));
	}

}
