<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Jesús Macias <jmacias@solidgear.es>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib\Storage;

use Icewind\SMB\Exception\ConnectException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\ForbiddenException;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\NativeServer;
use Icewind\SMB\Server;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Cache\CappedMemoryCache;
use OC\Files\Filesystem;
use OCP\Files\StorageNotAvailableException;

class SMB extends \OC\Files\Storage\Common {
	/**
	 * @var \Icewind\SMB\Server
	 */
	protected $server;

	/**
	 * @var \Icewind\SMB\Share
	 */
	protected $share;

	/** @var string	The folder that will act as root */
	protected $root;

	/**
	 * @var \Icewind\SMB\FileInfo[]
	 */
	protected $statCache;

	/** @var bool */
	protected $isInitialized = false;

	/** @var string Host where the connection should be made */
	protected $host;

	/** @var string User to connect*/
	protected $user;

	/** @var string */
	protected $password;

	/** @var string The share name that we'll use */
	protected $shareName;

	public function __construct($params) {
		if (!isset($params['host']) || !isset($params['user']) || !isset($params['password'])  || !isset($params['share'])) {
			throw new \Exception('Invalid configuration');
		}

		$this->host      = $params['host'];
		$this->user      = $params['user'];
		$this->password  = $params['password'];
		$this->shareName = $params['share'];
		$this->root      = isset($params['root']) ? $params['root'] : '/';

		if (!$this->root || $this->root[0] != '/') {
			$this->root = '/' . $this->root;
		}
		if (substr($this->root, -1, 1) != '/') {
			$this->root .= '/';
		}

		$this->statCache = new CappedMemoryCache();
	}

	/**
	 * initializes the Samba server connection and share
	 *
	 * @throws StorageNotAvailableException
	 */
	public function init() {
		if($this->isInitialized) {
			return;
		}

		if($this->password === '' && $this->user !== '') {
			throw new StorageNotAvailableException('Password required when username given');
		}

		$serverClass = Server::NativeAvailable() ? 'NativeServer' : 'Server';
		$this->server = new $serverClass($this->host, $this->user, $this->password);
		$this->share = $this->server->getShare(trim($this->shareName, '/'));

		$this->isInitialized = true;
	}

	/**
	 * @return string
	 */
	public function getId() {
		$this->init();
		// FIXME: double slash to keep compatible with the old storage ids,
		// failure to do so will lead to creation of a new storage id and
		// loss of shares from the storage
		return 'smb::' . $this->server->getUser() . '@' . $this->server->getHost() . '//' . $this->share->getName() . '/' . $this->root;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function buildPath($path) {
		return Filesystem::normalizePath($this->root . '/' . $path, true, false, true);
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo
	 * @throws StorageNotAvailableException
	 */
	protected function getFileInfo($path) {
		$this->init();
		try {
			$path = $this->buildPath($path);
			if (!isset($this->statCache[$path])) {
				$this->statCache[$path] = $this->share->stat($path);
			}
			return $this->statCache[$path];
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo[]
	 * @throws StorageNotAvailableException
	 */
	protected function getFolderContents($path) {
		$this->init();
		try {
			$path = $this->buildPath($path);
			$files = $this->share->dir($path);
			foreach ($files as $file) {
				$this->statCache[$path . '/' . $file->getName()] = $file;
			}
			return $files;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
		}
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
	 * @throws StorageNotAvailableException
	 * @throws \Icewind\SMB\Exception\FileInUseException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function unlink($path) {
		$this->init();
		try {
			if ($this->is_dir($path)) {
				return $this->rmdir($path);
			} else {
				$path = $this->buildPath($path);
				unset($this->statCache[$path]);
				$this->share->del($path);
				return true;
			}
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
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
		$this->init();
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
	 * @throws StorageNotAvailableException
	 */
	public function fopen($path, $mode) {
		$this->init();
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
					$source = $this->share->write($fullPath);
					return CallBackWrapper::wrap($source, null, null, function () use ($fullPath) {
						unset($this->statCache[$fullPath]);
					});
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
					return CallbackWrapper::wrap($source, null, null, function () use ($tmpFile, $fullPath, $share) {
						unset($this->statCache[$fullPath]);
						$share->put($tmpFile, $fullPath);
						unlink($tmpFile);
					});
			}
			return false;
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param string $path
	 * @return bool
	 * @throws StorageNotAvailableException
	 * @throws \Icewind\SMB\Exception\FileInUseException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function rmdir($path) {
		$this->init();
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
		} catch (ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param string $path
	 * @param null $time
	 * @return bool
	 * @throws StorageNotAvailableException
	 */
	public function touch($path, $time = null) {
		$this->init();
		try {
			if (!$this->file_exists($path)) {
				$fh = $this->share->write($this->buildPath($path));
				fclose($fh);
				return true;
			}
			return false;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function opendir($path) {
		try {
			$files = $this->getFolderContents($path);
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		}
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
		} catch (ForbiddenException $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return bool
	 * @throws StorageNotAvailableException
	 */
	public function mkdir($path) {
		$this->init();
		$path = $this->buildPath($path);
		try {
			$this->share->mkdir($path);
			return true;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
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
		} catch (ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function isReadable($path) {
		try {
			$info = $this->getFileInfo($path);
			return !$info->isHidden();
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		}
	}

	public function isUpdatable($path) {
		try {
			$info = $this->getFileInfo($path);
			return !$info->isHidden() && !$info->isReadOnly();
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		}
	}

	/**
	 * check if smbclient is installed
	 */
	public static function checkDependencies() {
		return (
			(bool)\OC_Helper::findBinaryPath('smbclient')
			|| Server::NativeAvailable()
		) ? true : ['smbclient'];
	}

	/**
	 * Test a storage for availability
	 *
	 * @return bool
	 */
	public function test() {
		$this->init();
		try {
			return parent::test();
		} catch (Exception $e) {
			return false;
		}
	}
}
