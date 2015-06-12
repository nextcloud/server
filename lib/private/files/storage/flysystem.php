<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\GetWithMetadata;

abstract class Flysystem extends Common {
	/**
	 * @var Filesystem
	 */
	protected $flysystem;

	/**
	 * @var string
	 */
	protected $root = '';

	protected function buildFlySystem(AdapterInterface $adapter) {
		$this->flysystem = new Filesystem($adapter);
		$this->flysystem->addPlugin(new GetWithMetadata());
	}

	protected function buildPath($path) {
		$fullPath = \OC\Files\Filesystem::normalizePath($this->root . '/' . $path);
		return ltrim($fullPath, '/');
	}

	public function file_get_contents($path) {
		return $this->flysystem->read($this->buildPath($path));
	}

	public function file_put_contents($path, $data) {
		return $this->flysystem->put($this->buildPath($path), $data);
	}

	public function file_exists($path) {
		return $this->flysystem->has($this->buildPath($path));
	}

	public function unlink($path) {
		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		}
		try {
			return $this->flysystem->delete($this->buildPath($path));
		} catch (FileNotFoundException $e) {
			return false;
		}
	}

	public function rename($source, $target) {
		if ($this->file_exists($target)) {
			$this->unlink($target);
		}
		return $this->flysystem->rename($this->buildPath($source), $this->buildPath($target));
	}

	public function copy($source, $target) {
		if ($this->file_exists($target)) {
			$this->unlink($target);
		}
		return $this->flysystem->copy($this->buildPath($source), $this->buildPath($target));
	}

	public function filesize($path) {
		if ($this->is_dir($path)) {
			return 0;
		} else {
			return $this->flysystem->getSize($this->buildPath($path));
		}
	}

	public function mkdir($path) {
		if ($this->file_exists($path)) {
			return false;
		}
		return $this->flysystem->createDir($this->buildPath($path));
	}

	public function filemtime($path) {
		return $this->flysystem->getTimestamp($this->buildPath($path));
	}

	public function rmdir($path) {
		try {
			return @$this->flysystem->deleteDir($this->buildPath($path));
		} catch (FileNotFoundException $e) {
			return false;
		}
	}

	public function opendir($path) {
		try {
			$content = $this->flysystem->listContents($this->buildPath($path));
		} catch (FileNotFoundException $e) {
			return false;
		}
		$names = array_map(function ($object) {
			return $object['basename'];
		}, $content);
		return IteratorDirectory::wrap($names);
	}

	public function fopen($path, $mode) {
		$fullPath = $this->buildPath($path);
		$useExisting = true;
		switch ($mode) {
			case 'r':
			case 'rb':
				try {
					return $this->flysystem->readStream($fullPath);
				} catch (FileNotFoundException $e) {
					return false;
				}
			case 'w':
			case 'wb':
				$useExisting = false;
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				//emulate these
				if ($useExisting and $this->file_exists($path)) {
					if (!$this->isUpdatable($path)) {
						return false;
					}
					$tmpFile = $this->getCachedFile($path);
				} else {
					if (!$this->isCreatable(dirname($path))) {
						return false;
					}
					$tmpFile = \OCP\Files::tmpFile();
				}
				$source = fopen($tmpFile, $mode);
				return CallbackWrapper::wrap($source, null, null, function () use ($tmpFile, $fullPath) {
					$this->flysystem->putStream($fullPath, fopen($tmpFile, 'r'));
					unlink($tmpFile);
				});
		}
		return false;
	}

	public function touch($path, $mtime = null) {
		if ($this->file_exists($path)) {
			return false;
		} else {
			$this->file_put_contents($path, '');
			return true;
		}
	}

	public function stat($path) {
		$info = $this->flysystem->getWithMetadata($this->buildPath($path), ['timestamp', 'size']);
		return [
			'mtime' => $info['timestamp'],
			'size' => $info['size']
		];
	}

	public function filetype($path) {
		if ($path === '' or $path === '/' or $path === '.') {
			return 'dir';
		}
		try {
			$info = $this->flysystem->getMetadata($this->buildPath($path));
		} catch (FileNotFoundException $e) {
			return false;
		}
		return $info['type'];
	}
}
