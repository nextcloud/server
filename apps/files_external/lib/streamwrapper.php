<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

abstract class StreamWrapper extends Common {
	abstract public function constructUrl($path);

	public function mkdir($path) {
		return mkdir($this->constructUrl($path));
	}

	public function rmdir($path) {
		if ($this->file_exists($path)) {
			$dh = $this->opendir($path);
			while (($file = readdir($dh)) !== false) {
				if ($this->is_dir($path . '/' . $file)) {
					$this->rmdir($path . '/' . $file);
				} else {
					$this->unlink($path . '/' . $file);
				}
			}
			$success = rmdir($this->constructUrl($path));
			clearstatcache();
			return $success;
		} else {
			return false;
		}
	}

	public function opendir($path) {
		return opendir($this->constructUrl($path));
	}

	public function filetype($path) {
		return filetype($this->constructUrl($path));
	}

	public function isReadable($path) {
		return true; //not properly supported
	}

	public function isUpdatable($path) {
		return true; //not properly supported
	}

	public function file_exists($path) {
		return file_exists($this->constructUrl($path));
	}

	public function unlink($path) {
		$success = unlink($this->constructUrl($path));
		clearstatcache();
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
		}
	}

	public function getFile($path, $target) {
		return copy($this->constructUrl($path), $target);
	}

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
