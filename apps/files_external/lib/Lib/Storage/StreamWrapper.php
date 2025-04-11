<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

use OC\Files\Storage\Common;

abstract class StreamWrapper extends Common {

	abstract public function constructUrl(string $path): ?string;

	public function mkdir(string $path): bool {
		return mkdir($this->constructUrl($path));
	}

	public function rmdir(string $path): bool {
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

	public function opendir(string $path) {
		return opendir($this->constructUrl($path));
	}

	public function filetype(string $path): string|false {
		return @filetype($this->constructUrl($path));
	}

	public function file_exists(string $path): bool {
		return file_exists($this->constructUrl($path));
	}

	public function unlink(string $path): bool {
		$url = $this->constructUrl($path);
		$success = unlink($url);
		// normally unlink() is supposed to do this implicitly,
		// but doing it anyway just to be sure
		clearstatcache(false, $url);
		return $success;
	}

	public function fopen(string $path, string $mode) {
		return fopen($this->constructUrl($path), $mode);
	}

	public function touch(string $path, ?int $mtime = null): bool {
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

	public function getFile(string $path, string $target): bool {
		return copy($this->constructUrl($path), $target);
	}

	public function uploadFile(string $path, string $target): bool {
		return copy($path, $this->constructUrl($target));
	}

	public function rename(string $source, string $target): bool {
		return rename($this->constructUrl($source), $this->constructUrl($target));
	}

	public function stat(string $path): array|false {
		return stat($this->constructUrl($path));
	}
}
