<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\NativeServer;
use Icewind\SMB\Server;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Filesystem;

class SMB extends Common {
	/**
	 * @var \Icewind\SMB\Server
	 */
	protected $server;

	/**
	 * @var \Icewind\SMB\Share
	 */
	protected $share;

	/**
	 * @var \Icewind\SMB\FileInfo[]
	 */
	protected $statCache = array();

	public function __construct($params) {
		if (isset($params['host']) && isset($params['user']) && isset($params['password']) && isset($params['share'])) {
			if (Server::NativeAvailable()) {
				$this->server = new NativeServer($params['host'], $params['user'], $params['password']);
			} else {
				$this->server = new Server($params['host'], $params['user'], $params['password']);
			}
			$this->share = $this->server->getShare(trim($params['share'], '/'));

			$this->root = isset($params['root']) ? $params['root'] : '/';
			if (!$this->root || $this->root[0] != '/') {
				$this->root = '/' . $this->root;
			}
		} else {
			throw new \Exception('Invalid configuration');
		}
	}

	/**
	 * @return string
	 */
	public function getId() {
		return 'smb::' . $this->server->getUser() . '@' . $this->server->getHost() . '/' . $this->share->getName() . '/' . $this->root;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function buildPath($path) {
		return Filesystem::normalizePath($this->root . '/' . $path);
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo
	 */
	protected function getFileInfo($path) {
		$path = $this->buildPath($path);
		if (!isset($this->statCache[$path])) {
			$this->statCache[$path] = $this->share->stat($path);
		}
		return $this->statCache[$path];
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo[]
	 */
	protected function getFolderContents($path) {
		$path = $this->buildPath($path);
		$files = $this->share->dir($path);
		foreach ($files as $file) {
			$this->statCache[$path . '/' . $file->getName()] = $file;
		}
		return $files;
	}

	/**
	 * @param \Icewind\SMB\IFileInfo $info
	 * @return array
	 */
	protected function formatInfo($info) {
		return array(
			'size' => $info->getSize(),
			'mtime' => $info->getMTime()
		);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function stat($path) {
		return $this->formatInfo($this->getFileInfo($path));
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function unlink($path) {
		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		} else {
			$path = $this->buildPath($path);
			unset($this->statCache[$path]);
			$this->share->del($path);
			return true;
		}
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		if (!$path and $this->root == '/') {
			// mtime doesn't work for shares, but giving the nature of the backend,
			// doing a full update is still just fast enough
			return true;
		} else {
			$actualTime = $this->filemtime($path);
			return $actualTime > $time;
		}
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {
		$fullPath = $this->buildPath($path);
		try {
			switch ($mode) {
				case 'r':
				case 'rb':
					if (!$this->file_exists($path)) {
						return false;
					}
					return $this->share->read($fullPath);
				case 'w':
				case 'wb':
					return $this->share->write($fullPath);
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
					if (strrpos($path, '.') !== false) {
						$ext = substr($path, strrpos($path, '.'));
					} else {
						$ext = '';
					}
					if ($this->file_exists($path)) {
						if (!$this->isUpdatable($path)) {
							return false;
						}
						$tmpFile = $this->getCachedFile($path);
					} else {
						if (!$this->isCreatable(dirname($path))) {
							return false;
						}
						$tmpFile = \OCP\Files::tmpFile($ext);
					}
					$source = fopen($tmpFile, $mode);
					$share = $this->share;
					return CallBackWrapper::wrap($source, null, null, function () use ($tmpFile, $fullPath, $share) {
						$share->put($tmpFile, $fullPath);
						unlink($tmpFile);
					});
			}
			return false;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	public function rmdir($path) {
		try {
			$this->statCache = array();
			$content = $this->share->dir($this->buildPath($path));
			foreach ($content as $file) {
				if ($file->isDirectory()) {
					$this->rmdir($path . '/' . $file->getName());
				} else {
					$this->share->del($file->getPath());
				}
			}
			$this->share->rmdir($this->buildPath($path));
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	public function touch($path, $time = null) {
		if (!$this->file_exists($path)) {
			$fh = $this->share->write($this->buildPath($path));
			fclose($fh);
			return true;
		}
		return false;
	}

	public function opendir($path) {
		$files = $this->getFolderContents($path);
		$names = array_map(function ($info) {
			/** @var \Icewind\SMB\IFileInfo $info */
			return $info->getName();
		}, $files);
		return IteratorDirectory::wrap($names);
	}

	public function filetype($path) {
		try {
			return $this->getFileInfo($path)->isDirectory() ? 'dir' : 'file';
		} catch (NotFoundException $e) {
			return false;
		}
	}

	public function mkdir($path) {
		$path = $this->buildPath($path);
		try {
			$this->share->mkdir($path);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function file_exists($path) {
		try {
			$this->getFileInfo($path);
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * check if smbclient is installed
	 */
	public static function checkDependencies() {
		if (function_exists('shell_exec')) {
			$output = shell_exec('command -v smbclient 2> /dev/null');
			if (!empty($output)) {
				return true;
			}
		}
		return array('smbclient');
	}
}
