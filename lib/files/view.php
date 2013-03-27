<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Class to provide access to ownCloud filesystem via a "view", and methods for
 * working with files within that view (e.g. read, write, delete, etc.). Each
 * view is restricted to a set of directories via a virtual root. The default view
 * uses the currently logged in user's data directory as root (parts of
 * OC_Filesystem are merely a wrapper for OC_FilesystemView).
 *
 * Apps that need to access files outside of the user data folders (to modify files
 * belonging to a user other than the one currently logged in, for example) should
 * use this class directly rather than using OC_Filesystem, or making use of PHP's
 * built-in file manipulation functions. This will ensure all hooks and proxies
 * are triggered correctly.
 *
 * Filesystem functions are not called directly; they are passed to the correct
 * \OC\Files\Storage\Storage object
 */

namespace OC\Files;

class View {
	private $fakeRoot = '';
	private $internal_path_cache = array();
	private $storage_cache = array();

	public function __construct($root) {
		$this->fakeRoot = $root;
	}

	public function getAbsolutePath($path = '/') {
		if (!$path) {
			$path = '/';
		}
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
		return $this->fakeRoot . $path;
	}

	/**
	 * change the root to a fake root
	 *
	 * @param string $fakeRoot
	 * @return bool
	 */
	public function chroot($fakeRoot) {
		if (!$fakeRoot == '') {
			if ($fakeRoot[0] !== '/') {
				$fakeRoot = '/' . $fakeRoot;
			}
		}
		$this->fakeRoot = $fakeRoot;
	}

	/**
	 * get the fake root
	 *
	 * @return string
	 */
	public function getRoot() {
		return $this->fakeRoot;
	}

	/**
	 * get path relative to the root of the view
	 *
	 * @param string $path
	 * @return string
	 */
	public function getRelativePath($path) {
		if ($this->fakeRoot == '') {
			return $path;
		}
		if (strpos($path, $this->fakeRoot) !== 0) {
			return null;
		} else {
			$path = substr($path, strlen($this->fakeRoot));
			if (strlen($path) === 0) {
				return '/';
			} else {
				return $path;
			}
		}
	}

	/**
	 * get the mountpoint of the storage object for a path
	 * ( note: because a storage is not always mounted inside the fakeroot, the
	 * returned mountpoint is relative to the absolute root of the filesystem
	 * and doesn't take the chroot into account )
	 *
	 * @param string $path
	 * @return string
	 */
	public function getMountPoint($path) {
		return Filesystem::getMountPoint($this->getAbsolutePath($path));
	}

	/**
	 * resolve a path to a storage and internal path
	 *
	 * @param string $path
	 * @return array consisting of the storage and the internal path
	 */
	public function resolvePath($path) {
		return Filesystem::resolvePath($this->getAbsolutePath($path));
	}

	/**
	 * return the path to a local version of the file
	 * we need this because we can't know if a file is stored local or not from
	 * outside the filestorage and for some purposes a local file is needed
	 *
	 * @param string $path
	 * @return string
	 */
	public function getLocalFile($path) {
		$parent = substr($path, 0, strrpos($path, '/'));
		$path = $this->getAbsolutePath($path);
		list($storage, $internalPath) = Filesystem::resolvePath($path);
		if (Filesystem::isValidPath($parent) and $storage) {
			return $storage->getLocalFile($internalPath);
		} else {
			return null;
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getLocalFolder($path) {
		$parent = substr($path, 0, strrpos($path, '/'));
		$path = $this->getAbsolutePath($path);
		list($storage, $internalPath) = Filesystem::resolvePath($path);
		if (Filesystem::isValidPath($parent) and $storage) {
			return $storage->getLocalFolder($internalPath);
		} else {
			return null;
		}
	}

	/**
	 * the following functions operate with arguments and return values identical
	 * to those of their PHP built-in equivalents. Mostly they are merely wrappers
	 * for \OC\Files\Storage\Storage via basicOperation().
	 */
	public function mkdir($path) {
		return $this->basicOperation('mkdir', $path, array('create', 'write'));
	}

	public function rmdir($path) {
		return $this->basicOperation('rmdir', $path, array('delete'));
	}

	public function opendir($path) {
		return $this->basicOperation('opendir', $path, array('read'));
	}

	public function readdir($handle) {
		$fsLocal = new Storage\Local(array('datadir' => '/'));
		return $fsLocal->readdir($handle);
	}

	public function is_dir($path) {
		if ($path == '/') {
			return true;
		}
		return $this->basicOperation('is_dir', $path);
	}

	public function is_file($path) {
		if ($path == '/') {
			return false;
		}
		return $this->basicOperation('is_file', $path);
	}

	public function stat($path) {
		return $this->basicOperation('stat', $path);
	}

	public function filetype($path) {
		return $this->basicOperation('filetype', $path);
	}

	public function filesize($path) {
		return $this->basicOperation('filesize', $path);
	}

	public function readfile($path) {
		@ob_end_clean();
		$handle = $this->fopen($path, 'rb');
		if ($handle) {
			$chunkSize = 8192; // 8 kB chunks
			while (!feof($handle)) {
				echo fread($handle, $chunkSize);
				flush();
			}
			$size = $this->filesize($path);
			return $size;
		}
		return false;
	}

	public function isCreatable($path) {
		return $this->basicOperation('isCreatable', $path);
	}

	public function isReadable($path) {
		return $this->basicOperation('isReadable', $path);
	}

	public function isUpdatable($path) {
		return $this->basicOperation('isUpdatable', $path);
	}

	public function isDeletable($path) {
		return $this->basicOperation('isDeletable', $path);
	}

	public function isSharable($path) {
		return $this->basicOperation('isSharable', $path);
	}

	public function file_exists($path) {
		if ($path == '/') {
			return true;
		}
		return $this->basicOperation('file_exists', $path);
	}

	public function filemtime($path) {
		return $this->basicOperation('filemtime', $path);
	}

	public function touch($path, $mtime = null) {
		if (!is_null($mtime) and !is_numeric($mtime)) {
			$mtime = strtotime($mtime);
		}
		
		$hooks = array('touch');
		
		if (!$this->file_exists($path)) {
			$hooks[] = 'write';
		}
		
		return $this->basicOperation('touch', $path, $hooks, $mtime);
	}

	public function file_get_contents($path) {
		return $this->basicOperation('file_get_contents', $path, array('read'));
	}

	public function file_put_contents($path, $data) {
		if (is_resource($data)) { //not having to deal with streams in file_put_contents makes life easier
			$absolutePath = Filesystem::normalizePath($this->getAbsolutePath($path));
			if (\OC_FileProxy::runPreProxies('file_put_contents', $absolutePath, $data)
				&& Filesystem::isValidPath($path)) {
				$path = $this->getRelativePath($absolutePath);
				$exists = $this->file_exists($path);
				$run = true;
				if ($this->fakeRoot == Filesystem::getRoot() && ! Cache\Scanner::isIgnoredFile($path) ) {
					if (!$exists) {
						\OC_Hook::emit(
							Filesystem::CLASSNAME,
							Filesystem::signal_create,
							array(
								Filesystem::signal_param_path => $path,
								Filesystem::signal_param_run => &$run
							)
						);
					}
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						Filesystem::signal_write,
						array(
							Filesystem::signal_param_path => $path,
							Filesystem::signal_param_run => &$run
						)
					);
				}
				if (!$run) {
					return false;
				}
				$target = $this->fopen($path, 'w');
				if ($target) {
					list ($count, $result) = \OC_Helper::streamCopy($data, $target);
					fclose($target);
					fclose($data);
					if ($this->fakeRoot == Filesystem::getRoot() && ! Cache\Scanner::isIgnoredFile($path) ) {
						if (!$exists) {
							\OC_Hook::emit(
								Filesystem::CLASSNAME,
								Filesystem::signal_post_create,
								array(Filesystem::signal_param_path => $path)
							);
						}
						\OC_Hook::emit(
							Filesystem::CLASSNAME,
							Filesystem::signal_post_write,
							array(Filesystem::signal_param_path => $path)
						);
					}
					\OC_FileProxy::runPostProxies('file_put_contents', $absolutePath, $count);
					return $result;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return $this->basicOperation('file_put_contents', $path, array('create', 'write'), $data);
		}
	}

	public function unlink($path) {
		return $this->basicOperation('unlink', $path, array('delete'));
	}

	public function deleteAll($directory, $empty = false) {
		return $this->basicOperation('deleteAll', $directory, array('delete'), $empty);
	}

	public function rename($path1, $path2) {
		$postFix1 = (substr($path1, -1, 1) === '/') ? '/' : '';
		$postFix2 = (substr($path2, -1, 1) === '/') ? '/' : '';
		$absolutePath1 = Filesystem::normalizePath($this->getAbsolutePath($path1));
		$absolutePath2 = Filesystem::normalizePath($this->getAbsolutePath($path2));
		if (\OC_FileProxy::runPreProxies('rename', $absolutePath1, $absolutePath2)
			and Filesystem::isValidPath($path2)) {
			$path1 = $this->getRelativePath($absolutePath1);
			$path2 = $this->getRelativePath($absolutePath2);

			if ($path1 == null or $path2 == null) {
				return false;
			}
			$run = true;
			if ($this->fakeRoot == Filesystem::getRoot()) {
				\OC_Hook::emit(
					Filesystem::CLASSNAME, Filesystem::signal_rename,
					array(
						Filesystem::signal_param_oldpath => $path1,
						Filesystem::signal_param_newpath => $path2,
						Filesystem::signal_param_run => &$run
					)
				);
			}
			if ($run) {
				$mp1 = $this->getMountPoint($path1 . $postFix1);
				$mp2 = $this->getMountPoint($path2 . $postFix2);
				if ($mp1 == $mp2) {
					list($storage, $internalPath1) = Filesystem::resolvePath($absolutePath1 . $postFix1);
					list(, $internalPath2) = Filesystem::resolvePath($absolutePath2 . $postFix2);
					if ($storage) {
						$result = $storage->rename($internalPath1, $internalPath2);
					} else {
						$result = false;
					}
				} else {
					$source = $this->fopen($path1 . $postFix1, 'r');
					$target = $this->fopen($path2 . $postFix2, 'w');
					list($count, $result) = \OC_Helper::streamCopy($source, $target);
					list($storage1, $internalPath1) = Filesystem::resolvePath($absolutePath1 . $postFix1);
					$storage1->unlink($internalPath1);
				}
				if ($this->fakeRoot == Filesystem::getRoot()) {
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						Filesystem::signal_post_rename,
						array(
							Filesystem::signal_param_oldpath => $path1,
							Filesystem::signal_param_newpath => $path2
						)
					);
				}
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function copy($path1, $path2) {
		$postFix1 = (substr($path1, -1, 1) === '/') ? '/' : '';
		$postFix2 = (substr($path2, -1, 1) === '/') ? '/' : '';
		$absolutePath1 = Filesystem::normalizePath($this->getAbsolutePath($path1));
		$absolutePath2 = Filesystem::normalizePath($this->getAbsolutePath($path2));
		if (\OC_FileProxy::runPreProxies('copy', $absolutePath1, $absolutePath2) and Filesystem::isValidPath($path2)) {
			$path1 = $this->getRelativePath($absolutePath1);
			$path2 = $this->getRelativePath($absolutePath2);

			if ($path1 == null or $path2 == null) {
				return false;
			}
			$run = true;
			$exists = $this->file_exists($path2);
			if ($this->fakeRoot == Filesystem::getRoot()) {
				\OC_Hook::emit(
					Filesystem::CLASSNAME,
					Filesystem::signal_copy,
					array(
						Filesystem::signal_param_oldpath => $path1,
						Filesystem::signal_param_newpath => $path2,
						Filesystem::signal_param_run => &$run
					)
				);
				if ($run and !$exists) {
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						Filesystem::signal_create,
						array(
							Filesystem::signal_param_path => $path2,
							Filesystem::signal_param_run => &$run
						)
					);
				}
				if ($run) {
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						Filesystem::signal_write,
						array(
							Filesystem::signal_param_path => $path2,
							Filesystem::signal_param_run => &$run
						)
					);
				}
			}
			if ($run) {
				$mp1 = $this->getMountPoint($path1 . $postFix1);
				$mp2 = $this->getMountPoint($path2 . $postFix2);
				if ($mp1 == $mp2) {
					list($storage, $internalPath1) = Filesystem::resolvePath($absolutePath1 . $postFix1);
					list(, $internalPath2) = Filesystem::resolvePath($absolutePath2 . $postFix2);
					if ($storage) {
						$result = $storage->copy($internalPath1, $internalPath2);
					} else {
						$result = false;
					}
				} else {
					$source = $this->fopen($path1 . $postFix1, 'r');
					$target = $this->fopen($path2 . $postFix2, 'w');
					list($count, $result) = \OC_Helper::streamCopy($source, $target);
				}
				if ($this->fakeRoot == Filesystem::getRoot()) {
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						Filesystem::signal_post_copy,
						array(
							Filesystem::signal_param_oldpath => $path1,
							Filesystem::signal_param_newpath => $path2
						)
					);
					if (!$exists) {
						\OC_Hook::emit(
							Filesystem::CLASSNAME,
							Filesystem::signal_post_create,
							array(Filesystem::signal_param_path => $path2)
						);
					}
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						Filesystem::signal_post_write,
						array(Filesystem::signal_param_path => $path2)
					);
				}
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function fopen($path, $mode) {
		$hooks = array();
		switch ($mode) {
			case 'r':
			case 'rb':
				$hooks[] = 'read';
				break;
			case 'r+':
			case 'rb+':
			case 'w+':
			case 'wb+':
			case 'x+':
			case 'xb+':
			case 'a+':
			case 'ab+':
				$hooks[] = 'read';
				$hooks[] = 'write';
				break;
			case 'w':
			case 'wb':
			case 'x':
			case 'xb':
			case 'a':
			case 'ab':
				$hooks[] = 'write';
				break;
			default:
				\OC_Log::write('core', 'invalid mode (' . $mode . ') for ' . $path, \OC_Log::ERROR);
		}

		return $this->basicOperation('fopen', $path, $hooks, $mode);
	}

	public function toTmpFile($path) {
		if (Filesystem::isValidPath($path)) {
			$source = $this->fopen($path, 'r');
			if ($source) {
				$extension = pathinfo($path, PATHINFO_EXTENSION);
				$tmpFile = \OC_Helper::tmpFile($extension);
				file_put_contents($tmpFile, $source);
				return $tmpFile;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function fromTmpFile($tmpFile, $path) {
		if (Filesystem::isValidPath($path)) {
			if (!$tmpFile) {
				debug_print_backtrace();
			}
			$source = fopen($tmpFile, 'r');
			if ($source) {
				$this->file_put_contents($path, $source);
				unlink($tmpFile);
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function getMimeType($path) {
		return $this->basicOperation('getMimeType', $path);
	}

	public function hash($type, $path, $raw = false) {
		$postFix = (substr($path, -1, 1) === '/') ? '/' : '';
		$absolutePath = Filesystem::normalizePath($this->getAbsolutePath($path));
		if (\OC_FileProxy::runPreProxies('hash', $absolutePath) && Filesystem::isValidPath($path)) {
			$path = $this->getRelativePath($absolutePath);
			if ($path == null) {
				return false;
			}
			if (Filesystem::$loaded && $this->fakeRoot == Filesystem::getRoot()) {
				\OC_Hook::emit(
					Filesystem::CLASSNAME,
					Filesystem::signal_read,
					array(Filesystem::signal_param_path => $path)
				);
			}
			list($storage, $internalPath) = Filesystem::resolvePath($absolutePath . $postFix);
			if ($storage) {
				$result = $storage->hash($type, $internalPath, $raw);
				$result = \OC_FileProxy::runPostProxies('hash', $absolutePath, $result);
				return $result;
			}
		}
		return null;
	}

	public function free_space($path = '/') {
		return $this->basicOperation('free_space', $path);
	}

	/**
	 * @brief abstraction layer for basic filesystem functions: wrapper for \OC\Files\Storage\Storage
	 * @param string $operation
	 * @param string $path
	 * @param array $hooks (optional)
	 * @param mixed $extraParam (optional)
	 * @return mixed
	 *
	 * This method takes requests for basic filesystem functions (e.g. reading & writing
	 * files), processes hooks and proxies, sanitises paths, and finally passes them on to
	 * \OC\Files\Storage\Storage for delegation to a storage backend for execution
	 */
	private function basicOperation($operation, $path, $hooks = array(), $extraParam = null) {
		$postFix = (substr($path, -1, 1) === '/') ? '/' : '';
		$absolutePath = Filesystem::normalizePath($this->getAbsolutePath($path));
		if (\OC_FileProxy::runPreProxies($operation, $absolutePath, $extraParam) and Filesystem::isValidPath($path)) {
			$path = $this->getRelativePath($absolutePath);
			if ($path == null) {
				return false;
			}

			$run = $this->runHooks($hooks, $path);
			list($storage, $internalPath) = Filesystem::resolvePath($absolutePath . $postFix);
			if ($run and $storage) {
				if (!is_null($extraParam)) {
					$result = $storage->$operation($internalPath, $extraParam);
				} else {
					$result = $storage->$operation($internalPath);
				}
				$result = \OC_FileProxy::runPostProxies($operation, $this->getAbsolutePath($path), $result);
				if (Filesystem::$loaded and $this->fakeRoot == Filesystem::getRoot()) {
					if ($operation != 'fopen') { //no post hooks for fopen, the file stream is still open
						$this->runHooks($hooks, $path, true);
					}
				}
				return $result;
			}
		}
		return null;
	}

	private function runHooks($hooks, $path, $post = false) {
		$prefix = ($post) ? 'post_' : '';
		$run = true;
		if (Filesystem::$loaded and $this->fakeRoot == Filesystem::getRoot() && ! Cache\Scanner::isIgnoredFile($path) ) {
			foreach ($hooks as $hook) {
				if ($hook != 'read') {
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						$prefix . $hook,
						array(
							Filesystem::signal_param_run => &$run,
							Filesystem::signal_param_path => $path
						)
					);
				} elseif (!$post) {
					\OC_Hook::emit(
						Filesystem::CLASSNAME,
						$prefix . $hook,
						array(
							Filesystem::signal_param_path => $path
						)
					);
				}
			}
		}
		return $run;
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		return $this->basicOperation('hasUpdated', $path, array(), $time);
	}

	/**
	 * get the filesystem info
	 *
	 * @param string $path
	 * @return array
	 *
	 * returns an associative array with the following keys:
	 * - size
	 * - mtime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public function getFileInfo($path) {
		$data = array();
		if (!Filesystem::isValidPath($path)) {
			return $data;
		}
		$path = Filesystem::normalizePath($this->fakeRoot . '/' . $path);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = Filesystem::resolvePath($path);
		if ($storage) {
			$cache = $storage->getCache($internalPath);
			$permissionsCache = $storage->getPermissionsCache($internalPath);
			$user = \OC_User::getUser();

			if (!$cache->inCache($internalPath)) {
				$scanner = $storage->getScanner($internalPath);
				$scanner->scan($internalPath, Cache\Scanner::SCAN_SHALLOW);
			} else {
				$watcher = $storage->getWatcher($internalPath);
				$watcher->checkUpdate($internalPath);
			}

			$data = $cache->get($internalPath);

			if ($data and $data['fileid']) {
				if ($data['mimetype'] === 'httpd/unix-directory') {
					//add the sizes of other mountpoints to the folder
					$mountPoints = Filesystem::getMountPoints($path);
					foreach ($mountPoints as $mountPoint) {
						$subStorage = Filesystem::getStorage($mountPoint);
						if ($subStorage) {
							$subCache = $subStorage->getCache('');
							$rootEntry = $subCache->get('');
							$data['size'] += $rootEntry['size'];
						}
					}
				}

				$permissions = $permissionsCache->get($data['fileid'], $user);
				if ($permissions === -1) {
					$permissions = $storage->getPermissions($internalPath);
					$permissionsCache->set($data['fileid'], $user, $permissions);
				}
				$data['permissions'] = $permissions;
			}
		}
		return $data;
	}

	/**
	 * get the content of a directory
	 *
	 * @param string $directory path under datadirectory
	 * @return array
	 */
	public function getDirectoryContent($directory, $mimetype_filter = '') {
		$result = array();
		if (!Filesystem::isValidPath($directory)) {
			return $result;
		}
		$path = Filesystem::normalizePath($this->fakeRoot . '/' . $directory);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = Filesystem::resolvePath($path);
		if ($storage) {
			$cache = $storage->getCache($internalPath);
			$permissionsCache = $storage->getPermissionsCache($internalPath);
			$user = \OC_User::getUser();

			if ($cache->getStatus($internalPath) < Cache\Cache::COMPLETE) {
				$scanner = $storage->getScanner($internalPath);
				$scanner->scan($internalPath, Cache\Scanner::SCAN_SHALLOW);
			} else {
				$watcher = $storage->getWatcher($internalPath);
				$watcher->checkUpdate($internalPath);
			}

			$files = $cache->getFolderContents($internalPath); //TODO: mimetype_filter

			$ids = array();
			foreach ($files as $i => $file) {
				$files[$i]['type'] = $file['mimetype'] === 'httpd/unix-directory' ? 'dir' : 'file';
				$ids[] = $file['fileid'];

				$permissions = $permissionsCache->get($file['fileid'], $user);
				if ($permissions === -1) {
					$permissions = $storage->getPermissions($file['path']);
					$permissionsCache->set($file['fileid'], $user, $permissions);
				}
				$files[$i]['permissions'] = $permissions;
			}

			//add a folder for any mountpoint in this directory and add the sizes of other mountpoints to the folders
			$mountPoints = Filesystem::getMountPoints($path);
			$dirLength = strlen($path);
			foreach ($mountPoints as $mountPoint) {
				$subStorage = Filesystem::getStorage($mountPoint);
				if ($subStorage) {
					$subCache = $subStorage->getCache('');

					if ($subCache->getStatus('') === Cache\Cache::NOT_FOUND) {
						$subScanner = $subStorage->getScanner('');
						$subScanner->scanFile('');
					}

					$rootEntry = $subCache->get('');
					if ($rootEntry) {
						$relativePath = trim(substr($mountPoint, $dirLength), '/');
						if ($pos = strpos($relativePath, '/')) {
							//mountpoint inside subfolder add size to the correct folder
							$entryName = substr($relativePath, 0, $pos);
							foreach ($files as &$entry) {
								if ($entry['name'] === $entryName) {
									$entry['size'] += $rootEntry['size'];
								}
							}
						} else { //mountpoint in this folder, add an entry for it
							$rootEntry['name'] = $relativePath;
							$rootEntry['type'] = $rootEntry['mimetype'] === 'httpd/unix-directory' ? 'dir' : 'file';
							$subPermissionsCache = $subStorage->getPermissionsCache('');
							$permissions = $subPermissionsCache->get($rootEntry['fileid'], $user);
							if ($permissions === -1) {
								$permissions = $subStorage->getPermissions($rootEntry['path']);
								$subPermissionsCache->set($rootEntry['fileid'], $user, $permissions);
							}
							$rootEntry['permissions'] = $permissions;

							//remove any existing entry with the same name
							foreach ($files as $i => $file) {
								if ($file['name'] === $rootEntry['name']) {
									unset($files[$i]);
									break;
								}
							}
							$files[] = $rootEntry;
						}
					}
				}
			}

			if ($mimetype_filter) {
				foreach ($files as $file) {
					if (strpos($mimetype_filter, '/')) {
						if ($file['mimetype'] === $mimetype_filter) {
							$result[] = $file;
						}
					} else {
						if ($file['mimepart'] === $mimetype_filter) {
							$result[] = $file;
						}
					}
				}
			} else {
				$result = $files;
			}
		}
		return $result;
	}

	/**
	 * change file metadata
	 *
	 * @param string $path
	 * @param array $data
	 * @return int
	 *
	 * returns the fileid of the updated file
	 */
	public function putFileInfo($path, $data) {
		$path = Filesystem::normalizePath($this->fakeRoot . '/' . $path);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = Filesystem::resolvePath($path);
		if ($storage) {
			$cache = $storage->getCache($path);

			if (!$cache->inCache($internalPath)) {
				$scanner = $storage->getScanner($internalPath);
				$scanner->scan($internalPath, Cache\Scanner::SCAN_SHALLOW);
			}

			return $cache->put($internalPath, $data);
		} else {
			return -1;
		}
	}

	/**
	 * search for files with the name matching $query
	 *
	 * @param string $query
	 * @return array
	 */
	public function search($query) {
		return $this->searchCommon('%' . $query . '%', 'search');
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $query
	 * @return array
	 */
	public function searchByMime($mimetype) {
		return $this->searchCommon($mimetype, 'searchByMime');
	}

	/**
	 * @param string $query
	 * @param string $method
	 * @return array
	 */
	private function searchCommon($query, $method) {
		$files = array();
		$rootLength = strlen($this->fakeRoot);

		$mountPoint = Filesystem::getMountPoint($this->fakeRoot);
		$storage = Filesystem::getStorage($mountPoint);
		if ($storage) {
			$cache = $storage->getCache('');

			$results = $cache->$method($query);
			foreach ($results as $result) {
				if (substr($mountPoint . $result['path'], 0, $rootLength) === $this->fakeRoot) {
					$result['path'] = substr($mountPoint . $result['path'], $rootLength);
					$files[] = $result;
				}
			}

			$mountPoints = Filesystem::getMountPoints($this->fakeRoot);
			foreach ($mountPoints as $mountPoint) {
				$storage = Filesystem::getStorage($mountPoint);
				if ($storage) {
					$cache = $storage->getCache('');

					$relativeMountPoint = substr($mountPoint, $rootLength);
					$results = $cache->$method($query);
					foreach ($results as $result) {
						$result['path'] = $relativeMountPoint . $result['path'];
						$files[] = $result;
					}
				}
			}
		}
		return $files;
	}

	/**
	* Get the owner for a file or folder
	*
	* @param string $path
	* @return string
	*/
	public function getOwner($path) {
		return $this->basicOperation('getOwner', $path);
	}

	/**
	 * get the ETag for a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getETag($path) {
		/**
		 * @var Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = $this->resolvePath($path);
		if ($storage) {
			return $storage->getETag($internalPath);
		} else {
			return null;
		}
	}

	/**
	 * Get the path of a file by id, relative to the view
	 *
	 * Note that the resulting path is not guarantied to be unique for the id, multiple paths can point to the same file
	 *
	 * @param int $id
	 * @return string
	 */
	public function getPath($id) {
		list($storage, $internalPath) = Cache\Cache::getById($id);
		$mounts = Mount::findByStorageId($storage);
		foreach ($mounts as $mount) {
			/**
			 * @var \OC\Files\Mount $mount
			 */
			$fullPath = $mount->getMountPoint() . $internalPath;
			if (!is_null($path = $this->getRelativePath($fullPath))) {
				return $path;
			}
		}
		return null;
	}
}
