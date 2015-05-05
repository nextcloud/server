<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Files\Storage\Wrapper;

use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\Encryption\Update;
use OC\Encryption\Util;
use OC\Files\Filesystem;
use OC\Files\Storage\LocalTempFileTrait;
use OCP\Encryption\IFile;
use OCP\Encryption\IManager;
use OCP\Encryption\Keys\IStorage;
use OCP\Files\Mount\IMountPoint;
use OCP\ILogger;

class Encryption extends Wrapper {

	use LocalTempFileTrait;

	/** @var string */
	private $mountPoint;

	/** @var \OC\Encryption\Util */
	private $util;

	/** @var \OCP\Encryption\IManager */
	private $encryptionManager;

	/** @var \OCP\ILogger */
	private $logger;

	/** @var string */
	private $uid;

	/** @var array */
	private $unencryptedSize;

	/** @var \OCP\Encryption\IFile */
	private $fileHelper;

	/** @var IMountPoint */
	private $mount;

	/** @var \OCP\Encryption\Keys\IStorage */
	private $keyStorage;

	/** @var \OC\Encryption\Update */
	private $update;

	/**
	 * @param array $parameters
	 * @param \OCP\Encryption\IManager $encryptionManager
	 * @param \OC\Encryption\Util $util
	 * @param \OCP\ILogger $logger
	 * @param \OCP\Encryption\IFile $fileHelper
	 * @param string $uid user who perform the read/write operation (null for public access)
	 * @param IStorage $keyStorage
	 * @param Update $update
	 */
	public function __construct(
			$parameters,
			IManager $encryptionManager = null,
			Util $util = null,
			ILogger $logger = null,
			IFile $fileHelper = null,
			$uid = null,
			IStorage $keyStorage = null,
			Update $update = null
		) {

		$this->mountPoint = $parameters['mountPoint'];
		$this->mount = $parameters['mount'];
		$this->encryptionManager = $encryptionManager;
		$this->util = $util;
		$this->logger = $logger;
		$this->uid = $uid;
		$this->fileHelper = $fileHelper;
		$this->keyStorage = $keyStorage;
		$this->unencryptedSize = array();
		$this->update = $update;
		parent::__construct($parameters);
	}

	/**
	 * see http://php.net/manual/en/function.filesize.php
	 * The result for filesize when called on a folder is required to be 0
	 *
	 * @param string $path
	 * @return int
	 */
	public function filesize($path) {
		$fullPath = $this->getFullPath($path);

		$info = $this->getCache()->get($path);
		if (isset($this->unencryptedSize[$fullPath])) {
			$size = $this->unencryptedSize[$fullPath];

			if (isset($info['fileid'])) {
				$info['encrypted'] = true;
				$info['size'] = $size;
				$this->getCache()->put($path, $info);
			}
			return $size;
		}

		if (isset($info['fileid']) && $info['encrypted']) {
			return $info['size'];
		}
		return $this->storage->filesize($path);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getMetaData($path) {
		$data = $this->storage->getMetaData($path);
		if (is_null($data)) {
			return null;
		}
		$fullPath = $this->getFullPath($path);

		if (isset($this->unencryptedSize[$fullPath])) {
			$data['encrypted'] = true;
			$data['size'] = $this->unencryptedSize[$fullPath];
		} else {
			$info = $this->getCache()->get($path);
			if (isset($info['fileid']) && $info['encrypted']) {
				$data['encrypted'] = true;
				$data['size'] = $info['size'];
			}
		}

		return $data;
	}
	/**
	 * see http://php.net/manual/en/function.file_get_contents.php
	 *
	 * @param string $path
	 * @return string
	 */
	public function file_get_contents($path) {

		$encryptionModule = $this->getEncryptionModule($path);
		$info = $this->getCache()->get($path);

		if ($encryptionModule || $info['encrypted'] === true) {
			$handle = $this->fopen($path, "r");
			if (!$handle) {
				return false;
			}
			$data = stream_get_contents($handle);
			fclose($handle);
			return $data;
		}
		return $this->storage->file_get_contents($path);
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		// file put content will always be translated to a stream write
		$handle = $this->fopen($path, 'w');
		$written = fwrite($handle, $data);
		fclose($handle);
		return $written;
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function unlink($path) {
		$fullPath = $this->getFullPath($path);
		if ($this->util->isExcluded($fullPath)) {
			return $this->storage->unlink($path);
		}

		$encryptionModule = $this->getEncryptionModule($path);
		if ($encryptionModule) {
			$this->keyStorage->deleteAllFileKeys($this->getFullPath($path),
				$encryptionModule->getId());
		}

		return $this->storage->unlink($path);
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function rename($path1, $path2) {
		$source = $this->getFullPath($path1);
		if ($this->util->isExcluded($source)) {
			return $this->storage->rename($path1, $path2);
		}

		$result = $this->storage->rename($path1, $path2);
		if ($result) {
			$target = $this->getFullPath($path2);
			if (isset($this->unencryptedSize[$source])) {
				$this->unencryptedSize[$target] = $this->unencryptedSize[$source];
			}
			$keysRenamed = $this->keyStorage->renameKeys($source, $target);
			if ($keysRenamed &&
				dirname($source) !== dirname($target) &&
				$this->util->isFile($target)
			) {
				$this->update->update($target);
			}
		}

		return $result;
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function copy($path1, $path2) {
		$fullPath1 = $this->getFullPath($path1);
		if ($this->util->isExcluded($fullPath1)) {
			return $this->storage->copy($path1, $path2);
		}

		$source = $this->getFullPath($path1);
		$result = $this->storage->copy($path1, $path2);
		if ($result) {
			$target = $this->getFullPath($path2);
			$keysCopied = $this->keyStorage->copyKeys($source, $target);
			if ($keysCopied &&
				dirname($source) !== dirname($target) &&
				$this->util->isFile($target)
			) {
				$this->update->update($target);
			}
		}

		return $result;
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {

		$encryptionEnabled = $this->encryptionManager->isEnabled();
		$shouldEncrypt = false;
		$encryptionModule = null;
		$rawHeader = $this->getHeader($path);
		$header = $this->util->readHeader($rawHeader);
		$fullPath = $this->getFullPath($path);
		$encryptionModuleId = $this->util->getEncryptionModuleId($header);

		$size = $unencryptedSize = 0;
		$targetExists = $this->file_exists($path);
		$targetIsEncrypted = false;
		if ($targetExists) {
			// in case the file exists we require the explicit module as
			// specified in the file header - otherwise we need to fail hard to
			// prevent data loss on client side
			if (!empty($encryptionModuleId)) {
				$targetIsEncrypted = true;
				$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
			}

			$size = $this->storage->filesize($path);
			$unencryptedSize = $this->filesize($path);
		}

		try {

			if (
				$mode === 'w'
				|| $mode === 'w+'
				|| $mode === 'wb'
				|| $mode === 'wb+'
			) {
				if ($encryptionEnabled) {
					// if $encryptionModuleId is empty, the default module will be used
					$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
					$shouldEncrypt = $encryptionModule->shouldEncrypt($fullPath);
				}
			} else {
				$info = $this->getCache()->get($path);
				// only get encryption module if we found one in the header
				// or if file should be encrypted according to the file cache
				if (!empty($encryptionModuleId)) {
					$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
					$shouldEncrypt = true;
				} else if(empty($encryptionModuleId) && $info['encrypted'] === true) {
					// we come from a old installation. No header and/or no module defined
					// but the file is encrypted. In this case we need to use the
					// OC_DEFAULT_MODULE to read the file
					$encryptionModule = $this->encryptionManager->getEncryptionModule('OC_DEFAULT_MODULE');
					$shouldEncrypt = true;
				}
			}
		} catch (ModuleDoesNotExistsException $e) {
			$this->logger->warning('Encryption module "' . $encryptionModuleId .
				'" not found, file will be stored unencrypted (' . $e->getMessage() . ')');
		}

		// encryption disabled on write of new file and write to existing unencrypted file -> don't encrypt
		if (!$encryptionEnabled || !$this->mount->getOption('encrypt', true)) {
			if (!$targetExists || !$targetIsEncrypted) {
				$shouldEncrypt = false;
			}
		}

		if($shouldEncrypt === true && !$this->util->isExcluded($fullPath) && $encryptionModule !== null) {
			$source = $this->storage->fopen($path, $mode);
			$handle = \OC\Files\Stream\Encryption::wrap($source, $path, $fullPath, $header,
				$this->uid, $encryptionModule, $this->storage, $this, $this->util, $this->fileHelper, $mode,
				$size, $unencryptedSize, strlen($rawHeader));
			return $handle;
		} else {
			return $this->storage->fopen($path, $mode);
		}
	}

	/**
	 * get the path to a local version of the file.
	 * The local version of the file can be temporary and doesn't have to be persistent across requests
	 *
	 * @param string $path
	 * @return string
	 */
	public function getLocalFile($path) {
		if ($this->encryptionManager->isEnabled()) {
			return $this->getCachedFile($path);
		}
		return $this->storage->getLocalFile($path);
	}

	/**
	 * Returns the wrapped storage's value for isLocal()
	 *
	 * @return bool wrapped storage's isLocal() value
	 */
	public function isLocal() {
		if ($this->encryptionManager->isEnabled()) {
			return false;
		}
		return $this->storage->isLocal();
	}

	/**
	 * see http://php.net/manual/en/function.stat.php
	 * only the following keys are required in the result: size and mtime
	 *
	 * @param string $path
	 * @return array
	 */
	public function stat($path) {
		$stat = $this->storage->stat($path);
		$fileSize = $this->filesize($path);
		$stat['size'] = $fileSize;
		$stat[7] = $fileSize;
		return $stat;
	}

	/**
	 * see http://php.net/manual/en/function.hash.php
	 *
	 * @param string $type
	 * @param string $path
	 * @param bool $raw
	 * @return string
	 */
	public function hash($type, $path, $raw = false) {
		$fh = $this->fopen($path, 'rb');
		$ctx = hash_init($type);
		hash_update_stream($ctx, $fh);
		fclose($fh);
		return hash_final($ctx, $raw);
	}

	/**
	 * return full path, including mount point
	 *
	 * @param string $path relative to mount point
	 * @return string full path including mount point
	 */
	protected function getFullPath($path) {
		return Filesystem::normalizePath($this->mountPoint . '/' . $path);
	}

	/**
	 * read header from file
	 *
	 * @param string $path
	 * @return array
	 */
	protected function getHeader($path) {
		$header = '';
		if ($this->storage->file_exists($path)) {
			$handle = $this->storage->fopen($path, 'r');
			$firstBlock = fread($handle, $this->util->getHeaderSize());
			fclose($handle);
			if (substr($firstBlock, 0, strlen(Util::HEADER_START)) === Util::HEADER_START) {
				$header = $firstBlock;
			}
		}
		return $header;
	}

	/**
	 * read encryption module needed to read/write the file located at $path
	 *
	 * @param string $path
	 * @return null|\OCP\Encryption\IEncryptionModule
	 * @throws ModuleDoesNotExistsException
	 * @throws \Exception
	 */
	protected function getEncryptionModule($path) {
		$encryptionModule = null;
		$rawHeader = $this->getHeader($path);
		$header = $this->util->readHeader($rawHeader);
		$encryptionModuleId = $this->util->getEncryptionModuleId($header);
		if (!empty($encryptionModuleId)) {
			try {
				$encryptionModule = $this->encryptionManager->getEncryptionModule($encryptionModuleId);
			} catch (ModuleDoesNotExistsException $e) {
				$this->logger->critical('Encryption module defined in "' . $path . '" not loaded!');
				throw $e;
			}
		}
		return $encryptionModule;
	}

	public function updateUnencryptedSize($path, $unencryptedSize) {
		$this->unencryptedSize[$path] = $unencryptedSize;
	}
}
