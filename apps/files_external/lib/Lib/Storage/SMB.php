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

use Icewind\SMB\Exception\AlreadyExistsException;
use Icewind\SMB\Exception\ConnectException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\ForbiddenException;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\FileInfo;
use Icewind\SMB\NativeServer;
use Icewind\SMB\Server;
use Icewind\SMB\Share;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Cache\CappedMemoryCache;
use OC\Files\Filesystem;
use OC\Files\Storage\Common;
use OCP\Files\StorageNotAvailableException;
use OCP\Util;

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
	 * @var string
	 */
	protected $root;

	/**
	 * @var \Icewind\SMB\FileInfo[]
	 */
	protected $statCache;

	public function __construct($params) {
		$loggedParams = $params;
		// remove password from log if it is set
		if (!empty($loggedParams['password'])) {
			$loggedParams['password'] = '***removed***';
		}
		$this->log('enter: '.__FUNCTION__.'('.json_encode($loggedParams).')');

		if (isset($params['host']) && isset($params['user']) && isset($params['password']) && isset($params['share'])) {
			if (Server::NativeAvailable()) {
				$this->log('using native libsmbclient');
				$this->server = new NativeServer($params['host'], $params['user'], $params['password']);
			} else {
				$this->log('falling back to smbclient');
				$this->server = new Server($params['host'], $params['user'], $params['password']);
			}
			$this->share = $this->server->getShare(trim($params['share'], '/'));

			$this->root = isset($params['root']) ? $params['root'] : '/';
			if (!$this->root || $this->root[0] != '/') {
				$this->root = '/' . $this->root;
			}
			if (substr($this->root, -1, 1) != '/') {
				$this->root .= '/';
			}
		} else {
			$ex = new \Exception('Invalid configuration');
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		$this->statCache = new CappedMemoryCache();
		$this->log('leave: '.__FUNCTION__.', getId:'.$this->getId());
	}

	/**
	 * @return string
	 */
	public function getId() {
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
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = Filesystem::normalizePath($this->root . '/' . $path, true, false, true);
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo
	 * @throws StorageNotAvailableException
	 * @throws ForbiddenException
	 * @throws NotFoundException
	 */
	protected function getFileInfo($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
			$path = $this->buildPath($path);
			if (!isset($this->statCache[$path])) {
				try {
					$this->log("stat fetching '$path'");
					try {
						$this->statCache[$path] = $this->share->stat($path);
					} catch (NotFoundException $e) {
						if ($this->share instanceof Share) {
							// smbclient may have problems with the allinfo cmd
							$this->log("stat for '$path' failed, trying to read parent dir");
							$infos = $this->share->dir(dirname($path));
							foreach ($infos as $fileInfo) {
								if ($fileInfo->getName() === basename($path)) {
									$this->statCache[$path] = $fileInfo;
									break;
								}
							}
							if (empty($this->statCache[$path])) {
								$this->leave(__FUNCTION__, $e);
								throw $e;
							}
						} else {
							// trust the results of libsmb
							$this->leave(__FUNCTION__, $e);
							throw $e;
						}
					}
					if ($this->isRootDir($path) && $this->statCache[$path]->isHidden()) {
						$this->log("unhiding stat for '$path'");
						// make root never hidden, may happen when accessing a shared drive (mode is 22, archived and readonly - neither is true ... whatever)
						if ($this->statCache[$path]->isReadOnly()) {
							$mode = FileInfo::MODE_DIRECTORY & FileInfo::MODE_READONLY;
						} else {
							$mode = FileInfo::MODE_DIRECTORY;
						}
						$this->statCache[$path] = new FileInfo($path, '', 0, $this->statCache[$path]->getMTime(), $mode);
					}
				} catch (ConnectException $e) {
					$ex = new StorageNotAvailableException(
						$e->getMessage(), $e->getCode(), $e);
					$this->leave(__FUNCTION__, $ex);
					throw $ex;
				} catch (ForbiddenException $e) {
					if ($this->remoteIsShare() && $this->isRootDir($path)) { //mtime may not work for share root
						$this->log("faking stat for forbidden '$path'");
						$this->statCache[$path] = new FileInfo($path, '', 0, $this->shareMTime(), FileInfo::MODE_DIRECTORY);
					} else {
						$this->leave(__FUNCTION__, $e);
						throw $e;
					}
				}
			} else {
				$this->log("stat cache hit for '$path'");
			}
			$result = $this->statCache[$path];
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo[]
	 * @throws StorageNotAvailableException
	 */
	protected function getFolderContents($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		try {
			$path = $this->buildPath($path);
			$result = [];
			$children = $this->share->dir($path);
			foreach ($children as $fileInfo) {
				// check if the file is readable before adding it to the list
				// can't use "isReadable" function here, use smb internals instead
				if ($fileInfo->isHidden()) {
					$this->log("{$fileInfo->getName()} isn't readable, skipping", Util::DEBUG);
				} else {
					$result[] = $fileInfo;
					//remember entry so we can answer file_exists and filetype without a full stat
					$this->statCache[$path . '/' . $fileInfo->getName()] = $fileInfo;
				}
			}
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * @param \Icewind\SMB\IFileInfo $info
	 * @return array
	 */
	protected function formatInfo($info) {
		$result = [
			'size' => $info->getSize(),
			'mtime' => $info->getMTime(),
		];
		if ($info->isDirectory()) {
			$result['type'] = 'dir';
		} else {
			$result['type'] = 'file';
		}
		return $result;
	}

	/**
	 * Rename the files
	 *
	 * @param string $source the old name of the path
	 * @param string $target the new name of the path
	 * @return bool true if the rename is successful, false otherwise
	 */
	public function rename($source, $target) {
		$this->log("enter: rename('$source', '$target')", Util::DEBUG);
		try {
			$result = $this->share->rename($this->root . $source, $this->root . $target);
			$this->removeFromCache($this->root . $source);
			$this->removeFromCache($this->root . $target);
		} catch (AlreadyExistsException $e) {
			$this->unlink($target);
			$result = $this->share->rename($this->root . $source, $this->root . $target);
			$this->removeFromCache($this->root . $source);
			$this->removeFromCache($this->root . $target);
			$this->swallow(__FUNCTION__, $e);
		} catch (\Exception $e) {
			$this->swallow(__FUNCTION__, $e);
			$result = false;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	private function removeFromCache($path) {
		$path = trim($path, '/');
		// TODO The CappedCache does not really clear by prefix. It just clears all.
		//$this->dirCache->clear($path);
		$this->statCache->clear($path);
		//$this->xattrCache->clear($path);
	}
	/**
	 * @param string $path
	 * @return array
	 */
	public function stat($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = $this->formatInfo($this->getFileInfo($path));
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * get the best guess for the modification time of the share
	 * NOTE: modification times do not bubble up the directory tree, basically
	 * we are just guessing a time
	 *
	 * @return int the calculated mtime for the folder
	 */
	private function shareMTime() {
		$this->log('enter: '.__FUNCTION__, Util::DEBUG);
		$files = $this->share->dir($this->root);
		$result = 0;
		foreach ($files as $fileInfo) {
			if ($fileInfo->getMTime() > $result) {
				$result = $fileInfo->getMTime();
			}
		}
		return $this->leave(__FUNCTION__, $result);
	}
	/**
	 * Check if the path is our root dir (not the smb one)
	 *
	 * @param string $path the path
	 * @return bool true if it's root, false if not
	 */
	private function isRootDir($path) {
		$this->log('enter: '.__FUNCTION__."($path)", Util::DEBUG);
		$result = $path === '' || $path === '/' || $path === '.';
		return $this->leave(__FUNCTION__, $result);
	}
	/**
	 * Check if our root points to a smb share
	 *
	 * @return bool true if our root points to a share false otherwise
	 */
	private function remoteIsShare() {
		$this->log('enter: '.__FUNCTION__, Util::DEBUG);
		$result = $this->share->getName() && (!$this->root || $this->root === '/');
		return $this->leave(__FUNCTION__, $result);
	}
	/**
	 * @param string $path
	 * @return bool
	 * @throws StorageNotAvailableException
	 */
	public function unlink($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			if ($this->is_dir($path)) {
				$result = $this->rmdir($path);
			} else {
				$path = $this->buildPath($path);
				unset($this->statCache[$path]);
				$this->share->del($path);
				$result = true;
			}
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		$this->log('enter: '.__FUNCTION__."($path, $time)");
		$actualTime = $this->filemtime($path);
		$result = $actualTime > $time;
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 * @throws StorageNotAvailableException
	 */
	public function fopen($path, $mode) {
		$this->log('enter: '.__FUNCTION__."($path, $mode)");
		$fullPath = $this->buildPath($path);
		$result = false;
		try {
			switch ($mode) {
				case 'r':
				case 'rb':
					if ($this->file_exists($path)) {
						$result = $this->share->read($fullPath);
					}
					break;
				case 'w':
				case 'wb':
					$source = $this->share->write($fullPath);
					$result = CallBackWrapper::wrap($source, null, null, function () use ($fullPath) {
						unset($this->statCache[$fullPath]);
					});
					break;
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
							break;
						}
						$tmpFile = $this->getCachedFile($path);
					} else {
						if (!$this->isCreatable(dirname($path))) {
							break;
						}
						$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
					}
					$source = fopen($tmpFile, $mode);
					$share = $this->share;
					$result = CallbackWrapper::wrap($source, null, null, function () use ($tmpFile, $fullPath, $share) {
						unset($this->statCache[$fullPath]);
						$share->put($tmpFile, $fullPath);
						unlink($tmpFile);
					});
			}
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function rmdir($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$this->removeFromCache($path);
			$content = $this->share->dir($this->buildPath($path));
			foreach ($content as $file) {
				if ($file->isDirectory()) {
					$this->rmdir($path . '/' . $file->getName());
				} else {
					$this->share->del($file->getPath());
				}
			}
			$this->share->rmdir($this->buildPath($path));
			$result = true;
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function touch($path, $time = null) {
		$this->log('enter: '.__FUNCTION__."($path, $time)");
		try {
			if (!$this->file_exists($path)) {
				$fh = $this->share->write($this->buildPath($path));
				fclose($fh);
				$result = true;
			} else {
				$result = false;
			}
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function opendir($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$files = $this->getFolderContents($path);
			$names = array_map(function ($info) {
				/** @var \Icewind\SMB\IFileInfo $info */
				return $info->getName();
			}, $files);
			$result = IteratorDirectory::wrap($names);
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function filetype($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$result = $this->getFileInfo($path)->isDirectory() ? 'dir' : 'file';
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function mkdir($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		$path = $this->buildPath($path);
		try {
			$result = $this->share->mkdir($path);
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		} catch (Exception $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function file_exists($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$this->getFileInfo($path);
			$result = true;
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ConnectException $e) {
			$ex = new StorageNotAvailableException(
				$e->getMessage(), $e->getCode(), $e);
			$this->leave(__FUNCTION__, $ex);
			throw $ex;
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function isReadable($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$info = $this->getFileInfo($path);
			$result = !$info->isHidden();
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function isUpdatable($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$info = $this->getFileInfo($path);
			// following windows behaviour for read-only folders: they can be written into
			// (https://support.microsoft.com/en-us/kb/326549 - "cause" section)
			$result = !$info->isHidden() && (!$info->isReadOnly() || $this->is_dir($path));
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
	}

	public function isDeletable($path) {
		$this->log('enter: '.__FUNCTION__."($path)");
		$result = false;
		try {
			$info = $this->getFileInfo($path);
			$result = !$info->isHidden() && !$info->isReadOnly();
		} catch (NotFoundException $e) {
			$this->swallow(__FUNCTION__, $e);
		} catch (ForbiddenException $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
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
		$this->log('enter: '.__FUNCTION__."()");
		$result = false;
		try {
			$result = parent::test();
		} catch (Exception $e) {
			$this->swallow(__FUNCTION__, $e);
		}
		return $this->leave(__FUNCTION__, $result);
	}

	/**
	 * @param string $message
	 * @param int $level
	 * @param string $from
	 */
	private function log($message, $level = Util::DEBUG, $from = 'wnd') {
		if (\OC::$server->getConfig()->getSystemValue('wnd.logging.enable', false) === true) {
			Util::writeLog($from, $message, $level);
		}
	}

	/**
	 * if wnd.logging.enable is set to true in the config will log a leave line
	 * with the given function, the return value or the exception
	 *
	 * @param $function
	 * @param mixed $result an exception will be logged and then returned
	 * @return mixed
	 */
	private function leave($function, $result) {
		if (\OC::$server->getConfig()->getSystemValue('wnd.logging.enable', false) === false) {
			//don't bother building log strings
			return $result;
		} else if ($result === true) {
			Util::writeLog('wnd', "leave: $function, return true", Util::DEBUG);
		} else if ($result === false) {
			Util::writeLog('wnd', "leave: $function, return false", Util::DEBUG);
		} else if (is_string($result)) {
			Util::writeLog('wnd', "leave: $function, return '$result'", Util::DEBUG);
		} else if (is_resource($result)) {
			Util::writeLog('wnd', "leave: $function, return resource", Util::DEBUG);
		} else if ($result instanceof \Exception) {
			Util::writeLog('wnd', "leave: $function, throw ".get_class($result)
				.' - code: '.$result->getCode()
				.' message: '.$result->getMessage()
				.' trace: '.$result->getTraceAsString(), Util::DEBUG);
		} else {
			Util::writeLog('wnd', "leave: $function, return ".json_encode($result, true), Util::DEBUG);
		}
		return $result;
	}

	private function swallow($function, \Exception $exception) {
		if (\OC::$server->getConfig()->getSystemValue('wnd.logging.enable', false) === true) {
			Util::writeLog('wnd', "$function swallowing ".get_class($exception)
				.' - code: '.$exception->getCode()
				.' message: '.$exception->getMessage()
				.' trace: '.$exception->getTraceAsString(), Util::DEBUG);
		}
	}

	/**
	 * immediately close / free connection
	 */
	public function __destruct() {
		unset($this->share);
	}
}
