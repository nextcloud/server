<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author aler9 <46489434+aler9@users.noreply.github.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Boris Rybalkin <ribalkin@gmail.com>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Klaas Freitag <freitag@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Martin Brugnara <martin@0x6d62.eu>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sjors van der Pluijm <sjors@desjors.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tigran Mkrtchyan <tigran.mkrtchyan@desy.de>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Storage;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\Storage\Wrapper\Jail;
use OCP\Constants;
use OCP\Files\ForbiddenException;
use OCP\Files\GenericFileException;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * for local filestore, we only have to map the paths
 */
class Local extends \OC\Files\Storage\Common {
	protected $datadir;

	protected $dataDirLength;

	protected $realDataDir;

	private IConfig $config;

	private IMimeTypeDetector $mimeTypeDetector;

	private $defUMask;

	protected bool $unlinkOnTruncate;

	protected bool $caseInsensitive = false;

	public function __construct($arguments) {
		if (!isset($arguments['datadir']) || !is_string($arguments['datadir'])) {
			throw new \InvalidArgumentException('No data directory set for local storage');
		}
		$this->datadir = str_replace('//', '/', $arguments['datadir']);
		// some crazy code uses a local storage on root...
		if ($this->datadir === '/') {
			$this->realDataDir = $this->datadir;
		} else {
			$realPath = realpath($this->datadir) ?: $this->datadir;
			$this->realDataDir = rtrim($realPath, '/') . '/';
		}
		if (!str_ends_with($this->datadir, '/')) {
			$this->datadir .= '/';
		}
		$this->dataDirLength = strlen($this->realDataDir);
		$this->config = \OC::$server->get(IConfig::class);
		$this->mimeTypeDetector = \OC::$server->get(IMimeTypeDetector::class);
		$this->defUMask = $this->config->getSystemValue('localstorage.umask', 0022);
		$this->caseInsensitive = $this->config->getSystemValueBool('localstorage.case_insensitive', false);

		// support Write-Once-Read-Many file systems
		$this->unlinkOnTruncate = $this->config->getSystemValueBool('localstorage.unlink_on_truncate', false);

		if (isset($arguments['isExternal']) && $arguments['isExternal'] && !$this->stat('')) {
			// data dir not accessible or available, can happen when using an external storage of type Local
			// on an unmounted system mount point
			throw new StorageNotAvailableException('Local storage path does not exist "' . $this->getSourcePath('') . '"');
		}
	}

	public function __destruct() {
	}

	public function getId() {
		return 'local::' . $this->datadir;
	}

	public function mkdir($path) {
		$sourcePath = $this->getSourcePath($path);
		$oldMask = umask($this->defUMask);
		$result = @mkdir($sourcePath, 0777, true);
		umask($oldMask);
		return $result;
	}

	public function rmdir($path) {
		if (!$this->isDeletable($path)) {
			return false;
		}
		try {
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->getSourcePath($path)),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			/**
			 * RecursiveDirectoryIterator on an NFS path isn't iterable with foreach
			 * This bug is fixed in PHP 5.5.9 or before
			 * See #8376
			 */
			$it->rewind();
			while ($it->valid()) {
				/**
				 * @var \SplFileInfo $file
				 */
				$file = $it->current();
				clearstatcache(true, $file->getRealPath());
				if (in_array($file->getBasename(), ['.', '..'])) {
					$it->next();
					continue;
				} elseif ($file->isFile() || $file->isLink()) {
					unlink($file->getPathname());
				} elseif ($file->isDir()) {
					rmdir($file->getPathname());
				}
				$it->next();
			}
			clearstatcache(true, $this->getSourcePath($path));
			return rmdir($this->getSourcePath($path));
		} catch (\UnexpectedValueException $e) {
			return false;
		}
	}

	public function opendir($path) {
		return opendir($this->getSourcePath($path));
	}

	public function is_dir($path) {
		if ($this->caseInsensitive && !$this->file_exists($path)) {
			return false;
		}
		if (str_ends_with($path, '/')) {
			$path = substr($path, 0, -1);
		}
		return is_dir($this->getSourcePath($path));
	}

	public function is_file($path) {
		if ($this->caseInsensitive && !$this->file_exists($path)) {
			return false;
		}
		return is_file($this->getSourcePath($path));
	}

	public function stat($path) {
		$fullPath = $this->getSourcePath($path);
		clearstatcache(true, $fullPath);
		if (!file_exists($fullPath)) {
			return false;
		}
		$statResult = @stat($fullPath);
		if (PHP_INT_SIZE === 4 && $statResult && !$this->is_dir($path)) {
			$filesize = $this->filesize($path);
			$statResult['size'] = $filesize;
			$statResult[7] = $filesize;
		}
		if (is_array($statResult)) {
			$statResult['full_path'] = $fullPath;
		}
		return $statResult;
	}

	/**
	 * @inheritdoc
	 */
	public function getMetaData($path) {
		try {
			$stat = $this->stat($path);
		} catch (ForbiddenException $e) {
			return null;
		}
		if (!$stat) {
			return null;
		}

		$permissions = Constants::PERMISSION_SHARE;
		$statPermissions = $stat['mode'];
		$isDir = ($statPermissions & 0x4000) === 0x4000 && !($statPermissions & 0x8000);
		if ($statPermissions & 0x0100) {
			$permissions += Constants::PERMISSION_READ;
		}
		if ($statPermissions & 0x0080) {
			$permissions += Constants::PERMISSION_UPDATE;
			if ($isDir) {
				$permissions += Constants::PERMISSION_CREATE;
			}
		}

		if (!($path === '' || $path === '/')) { // deletable depends on the parents unix permissions
			$parent = dirname($stat['full_path']);
			if (is_writable($parent)) {
				$permissions += Constants::PERMISSION_DELETE;
			}
		}

		$data = [];
		$data['mimetype'] = $isDir ? 'httpd/unix-directory' : $this->mimeTypeDetector->detectPath($path);
		$data['mtime'] = $stat['mtime'];
		if ($data['mtime'] === false) {
			$data['mtime'] = time();
		}
		if ($isDir) {
			$data['size'] = -1; //unknown
		} else {
			$data['size'] = $stat['size'];
		}
		$data['etag'] = $this->calculateEtag($path, $stat);
		$data['storage_mtime'] = $data['mtime'];
		$data['permissions'] = $permissions;
		$data['name'] = basename($path);

		return $data;
	}

	public function filetype($path) {
		$filetype = filetype($this->getSourcePath($path));
		if ($filetype == 'link') {
			$filetype = filetype(realpath($this->getSourcePath($path)));
		}
		return $filetype;
	}

	public function filesize($path): false|int|float {
		if (!$this->is_file($path)) {
			return 0;
		}
		$fullPath = $this->getSourcePath($path);
		if (PHP_INT_SIZE === 4) {
			$helper = new \OC\LargeFileHelper;
			return $helper->getFileSize($fullPath);
		}
		return filesize($fullPath);
	}

	public function isReadable($path) {
		return is_readable($this->getSourcePath($path));
	}

	public function isUpdatable($path) {
		return is_writable($this->getSourcePath($path));
	}

	public function file_exists($path) {
		if ($this->caseInsensitive) {
			$fullPath = $this->getSourcePath($path);
			$parentPath = dirname($fullPath);
			if (!is_dir($parentPath)) {
				return false;
			}
			$content = scandir($parentPath, SCANDIR_SORT_NONE);
			return is_array($content) && array_search(basename($fullPath), $content) !== false;
		} else {
			return file_exists($this->getSourcePath($path));
		}
	}

	public function filemtime($path) {
		$fullPath = $this->getSourcePath($path);
		clearstatcache(true, $fullPath);
		if (!$this->file_exists($path)) {
			return false;
		}
		if (PHP_INT_SIZE === 4) {
			$helper = new \OC\LargeFileHelper();
			return $helper->getFileMtime($fullPath);
		}
		return filemtime($fullPath);
	}

	public function touch($path, $mtime = null) {
		// sets the modification time of the file to the given value.
		// If mtime is nil the current time is set.
		// note that the access time of the file always changes to the current time.
		if ($this->file_exists($path) and !$this->isUpdatable($path)) {
			return false;
		}
		$oldMask = umask($this->defUMask);
		if (!is_null($mtime)) {
			$result = @touch($this->getSourcePath($path), $mtime);
		} else {
			$result = @touch($this->getSourcePath($path));
		}
		umask($oldMask);
		if ($result) {
			clearstatcache(true, $this->getSourcePath($path));
		}

		return $result;
	}

	public function file_get_contents($path) {
		return file_get_contents($this->getSourcePath($path));
	}

	public function file_put_contents($path, $data) {
		$oldMask = umask($this->defUMask);
		if ($this->unlinkOnTruncate) {
			$this->unlink($path);
		}
		$result = file_put_contents($this->getSourcePath($path), $data);
		umask($oldMask);
		return $result;
	}

	public function unlink($path) {
		if ($this->is_dir($path)) {
			return $this->rmdir($path);
		} elseif ($this->is_file($path)) {
			return unlink($this->getSourcePath($path));
		} else {
			return false;
		}
	}

	private function checkTreeForForbiddenItems(string $path) {
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
		foreach ($iterator as $file) {
			/** @var \SplFileInfo $file */
			if (Filesystem::isFileBlacklisted($file->getBasename())) {
				throw new ForbiddenException('Invalid path: ' . $file->getPathname(), false);
			}
		}
	}

	public function rename($source, $target): bool {
		$srcParent = dirname($source);
		$dstParent = dirname($target);

		if (!$this->isUpdatable($srcParent)) {
			\OC::$server->get(LoggerInterface::class)->error('unable to rename, source directory is not writable : ' . $srcParent, ['app' => 'core']);
			return false;
		}

		if (!$this->isUpdatable($dstParent)) {
			\OC::$server->get(LoggerInterface::class)->error('unable to rename, destination directory is not writable : ' . $dstParent, ['app' => 'core']);
			return false;
		}

		if (!$this->file_exists($source)) {
			\OC::$server->get(LoggerInterface::class)->error('unable to rename, file does not exists : ' . $source, ['app' => 'core']);
			return false;
		}

		if ($this->file_exists($target)) {
			if ($this->is_dir($target)) {
				$this->rmdir($target);
			} elseif ($this->is_file($target)) {
				$this->unlink($target);
			}
		}

		if ($this->is_dir($source)) {
			$this->checkTreeForForbiddenItems($this->getSourcePath($source));
		}

		if (@rename($this->getSourcePath($source), $this->getSourcePath($target))) {
			if ($this->caseInsensitive) {
				if (mb_strtolower($target) === mb_strtolower($source) && !$this->file_exists($target)) {
					return false;
				}
			}
			return true;
		}

		return $this->copy($source, $target) && $this->unlink($source);
	}

	public function copy($source, $target) {
		if ($this->is_dir($source)) {
			return parent::copy($source, $target);
		} else {
			$oldMask = umask($this->defUMask);
			if ($this->unlinkOnTruncate) {
				$this->unlink($target);
			}
			$result = copy($this->getSourcePath($source), $this->getSourcePath($target));
			umask($oldMask);
			if ($this->caseInsensitive) {
				if (mb_strtolower($target) === mb_strtolower($source) && !$this->file_exists($target)) {
					return false;
				}
			}
			return $result;
		}
	}

	public function fopen($path, $mode) {
		$sourcePath = $this->getSourcePath($path);
		if (!file_exists($sourcePath) && $mode === 'r') {
			return false;
		}
		$oldMask = umask($this->defUMask);
		if (($mode === 'w' || $mode === 'w+') && $this->unlinkOnTruncate) {
			$this->unlink($path);
		}
		$result = @fopen($sourcePath, $mode);
		umask($oldMask);
		return $result;
	}

	public function hash($type, $path, $raw = false): string|false {
		return hash_file($type, $this->getSourcePath($path), $raw);
	}

	public function free_space($path) {
		$sourcePath = $this->getSourcePath($path);
		// using !is_dir because $sourcePath might be a part file or
		// non-existing file, so we'd still want to use the parent dir
		// in such cases
		if (!is_dir($sourcePath)) {
			// disk_free_space doesn't work on files
			$sourcePath = dirname($sourcePath);
		}
		$space = (function_exists('disk_free_space') && is_dir($sourcePath)) ? disk_free_space($sourcePath) : false;
		if ($space === false || is_null($space)) {
			return \OCP\Files\FileInfo::SPACE_UNKNOWN;
		}
		return Util::numericToNumber($space);
	}

	public function search($query) {
		return $this->searchInDir($query);
	}

	public function getLocalFile($path) {
		return $this->getSourcePath($path);
	}

	/**
	 * @param string $query
	 * @param string $dir
	 * @return array
	 */
	protected function searchInDir($query, $dir = '') {
		$files = [];
		$physicalDir = $this->getSourcePath($dir);
		foreach (scandir($physicalDir) as $item) {
			if (\OC\Files\Filesystem::isIgnoredDir($item)) {
				continue;
			}
			$physicalItem = $physicalDir . '/' . $item;

			if (strstr(strtolower($item), strtolower($query)) !== false) {
				$files[] = $dir . '/' . $item;
			}
			if (is_dir($physicalItem)) {
				$files = array_merge($files, $this->searchInDir($query, $dir . '/' . $item));
			}
		}
		return $files;
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		if ($this->file_exists($path)) {
			return $this->filemtime($path) > $time;
		} else {
			return true;
		}
	}

	/**
	 * Get the source path (on disk) of a given path
	 *
	 * @param string $path
	 * @return string
	 * @throws ForbiddenException
	 */
	public function getSourcePath($path) {
		if (Filesystem::isFileBlacklisted($path)) {
			throw new ForbiddenException('Invalid path: ' . $path, false);
		}

		$fullPath = $this->datadir . $path;
		$currentPath = $path;
		$allowSymlinks = $this->config->getSystemValueBool('localstorage.allowsymlinks', false);
		if ($allowSymlinks || $currentPath === '') {
			return $fullPath;
		}
		$pathToResolve = $fullPath;
		$realPath = realpath($pathToResolve);
		while ($realPath === false) { // for non existing files check the parent directory
			$currentPath = dirname($currentPath);
			if ($currentPath === '' || $currentPath === '.') {
				return $fullPath;
			}
			$realPath = realpath($this->datadir . $currentPath);
		}
		if ($realPath) {
			$realPath = $realPath . '/';
		}
		if (substr($realPath, 0, $this->dataDirLength) === $this->realDataDir) {
			return $fullPath;
		}

		\OC::$server->get(LoggerInterface::class)->error("Following symlinks is not allowed ('$fullPath' -> '$realPath' not inside '{$this->realDataDir}')", ['app' => 'core']);
		throw new ForbiddenException('Following symlinks is not allowed', false);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLocal() {
		return true;
	}

	/**
	 * get the ETag for a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getETag($path) {
		return $this->calculateEtag($path, $this->stat($path));
	}

	private function calculateEtag(string $path, array $stat): string {
		if ($stat['mode'] & 0x4000 && !($stat['mode'] & 0x8000)) { // is_dir & not socket
			return parent::getETag($path);
		} else {
			if ($stat === false) {
				return md5('');
			}

			$toHash = '';
			if (isset($stat['mtime'])) {
				$toHash .= $stat['mtime'];
			}
			if (isset($stat['ino'])) {
				$toHash .= $stat['ino'];
			}
			if (isset($stat['dev'])) {
				$toHash .= $stat['dev'];
			}
			if (isset($stat['size'])) {
				$toHash .= $stat['size'];
			}

			return md5($toHash);
		}
	}

	private function canDoCrossStorageMove(IStorage $sourceStorage) {
		/** @psalm-suppress UndefinedClass */
		return $sourceStorage->instanceOfStorage(Local::class)
			// Don't treat ACLStorageWrapper like local storage where copy can be done directly.
			// Instead, use the slower recursive copying in php from Common::copyFromStorage with
			// more permissions checks.
			&& !$sourceStorage->instanceOfStorage('OCA\GroupFolders\ACL\ACLStorageWrapper')
			// Same for access control
			&& !$sourceStorage->instanceOfStorage(\OCA\FilesAccessControl\StorageWrapper::class)
			// when moving encrypted files we have to handle keys and the target might not be encrypted
			&& !$sourceStorage->instanceOfStorage(Encryption::class);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @param bool $preserveMtime
	 * @return bool
	 */
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime = false) {
		if ($this->canDoCrossStorageMove($sourceStorage)) {
			// resolve any jailed paths
			while ($sourceStorage->instanceOfStorage(Jail::class)) {
				/**
				 * @var \OC\Files\Storage\Wrapper\Jail $sourceStorage
				 */
				$sourceInternalPath = $sourceStorage->getUnjailedPath($sourceInternalPath);
				$sourceStorage = $sourceStorage->getUnjailedStorage();
			}
			/**
			 * @var \OC\Files\Storage\Local $sourceStorage
			 */
			$rootStorage = new Local(['datadir' => '/']);
			return $rootStorage->copy($sourceStorage->getSourcePath($sourceInternalPath), $this->getSourcePath($targetInternalPath));
		} else {
			return parent::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($this->canDoCrossStorageMove($sourceStorage)) {
			// resolve any jailed paths
			while ($sourceStorage->instanceOfStorage(Jail::class)) {
				/**
				 * @var \OC\Files\Storage\Wrapper\Jail $sourceStorage
				 */
				$sourceInternalPath = $sourceStorage->getUnjailedPath($sourceInternalPath);
				$sourceStorage = $sourceStorage->getUnjailedStorage();
			}
			/**
			 * @var \OC\Files\Storage\Local $sourceStorage
			 */
			$rootStorage = new Local(['datadir' => '/']);
			return $rootStorage->rename($sourceStorage->getSourcePath($sourceInternalPath), $this->getSourcePath($targetInternalPath));
		} else {
			return parent::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
	}

	public function writeStream(string $path, $stream, int $size = null): int {
		/** @var int|false $result We consider here that returned size will never be a float because we write less than 4GB */
		$result = $this->file_put_contents($path, $stream);
		if (is_resource($stream)) {
			fclose($stream);
		}
		if ($result === false) {
			throw new GenericFileException("Failed write stream to $path");
		} else {
			return $result;
		}
	}
}
