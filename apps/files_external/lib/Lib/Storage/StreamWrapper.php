<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

abstract class StreamWrapper extends \OC\Files\Storage\Common {

	/**
	 * @param string $path
	 * @return string|null
	 */
	abstract public function constructUrl($path): ?string;

	public function mkdir($path): bool {
		return mkdir($this->constructUrl($path));
	}

	public function rmdir($path): bool {
		if ($this->is_dir($path) && $this->isDeletable($path)) {
			$dh = $this->opendir($path);
			if (!is_resource($dh)) {
				return false;
			}
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

	public function filetype($path): string|false {
		return @filetype($this->constructUrl($path));
	}

	public function file_exists($path): bool {
		return file_exists($this->constructUrl($path));
	}

	public function unlink($path): bool {
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

	public function touch($path, $mtime = null): bool {
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
	public function getFile($path, $target): bool {
		return copy($this->constructUrl($path), $target);
	}

	/**
	 * @param string $target
	 */
	public function uploadFile($path, $target): bool {
		return copy($path, $this->constructUrl($target));
	}

	public function rename($source, $target): bool {
		return rename($this->constructUrl($source), $this->constructUrl($target));
	}

	public function stat($path): array|false {
		return stat($this->constructUrl($path));
	}
}
