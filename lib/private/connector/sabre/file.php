<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author chli1 <chli1@users.noreply.github.com>
 * @author Chris Wilson <chris+github@qwirx.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Connector\Sabre;

use OC\Connector\Sabre\Exception\EntityTooLarge;
use OC\Connector\Sabre\Exception\FileLocked;
use OC\Connector\Sabre\Exception\UnsupportedMediaType;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\Files\EntityTooLargeException;
use OCP\Files\InvalidContentException;
use OCP\Files\InvalidPathException;
use OCP\Files\LockNotAcquiredException;
use OCP\Files\NotPermittedException;
use OCP\Files\StorageNotAvailableException;
use OCP\Lock\ILockingProvider;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\IFile;

class File extends Node implements IFile {

	/**
	 * Updates the data
	 *
	 * The data argument is a readable stream resource.
	 *
	 * After a successful put operation, you may choose to return an ETag. The
	 * etag must always be surrounded by double-quotes. These quotes must
	 * appear in the actual string you're returning.
	 *
	 * Clients may use the ETag from a PUT request to later on make sure that
	 * when they update the file, the contents haven't changed in the mean
	 * time.
	 *
	 * If you don't plan to store the file byte-by-byte, and you return a
	 * different object on a subsequent GET you are strongly recommended to not
	 * return an ETag, and just return null.
	 *
	 * @param resource $data
	 *
	 * @throws Forbidden
	 * @throws UnsupportedMediaType
	 * @throws BadRequest
	 * @throws Exception
	 * @throws EntityTooLarge
	 * @throws ServiceUnavailable
	 * @return string|null
	 */
	public function put($data) {
		try {
			$exists = $this->fileView->file_exists($this->path);
			if ($this->info && $exists && !$this->info->isUpdateable()) {
				throw new Forbidden();
			}
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable("File is not updatable: " . $e->getMessage());
		}

		// verify path of the target
		$this->verifyPath();

		// chunked handling
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			return $this->createFileChunked($data);
		}

		list($partStorage) = $this->fileView->resolvePath($this->path);
		$needsPartFile = $this->needsPartFile($partStorage) && (strlen($this->path) > 1);

		if ($needsPartFile) {
			// mark file as partial while uploading (ignored by the scanner)
			$partFilePath = $this->path . '.ocTransferId' . rand() . '.part';
		} else {
			// upload file directly as the final path
			$partFilePath = $this->path;
		}

		$this->fileView->lockFile($this->path, ILockingProvider::LOCK_EXCLUSIVE);

		// the part file and target file might be on a different storage in case of a single file storage (e.g. single file share)
		/** @var \OC\Files\Storage\Storage $partStorage */
		list($partStorage, $internalPartPath) = $this->fileView->resolvePath($partFilePath);
		/** @var \OC\Files\Storage\Storage $storage */
		list($storage, $internalPath) = $this->fileView->resolvePath($this->path);
		try {
			$target = $partStorage->fopen($internalPartPath, 'wb');
			if ($target === false) {
				\OC_Log::write('webdav', '\OC\Files\Filesystem::fopen() failed', \OC_Log::ERROR);
				$partStorage->unlink($internalPartPath);
				// because we have no clue about the cause we can only throw back a 500/Internal Server Error
				throw new Exception('Could not write file contents');
			}
			list($count, ) = \OC_Helper::streamCopy($data, $target);
			fclose($target);

			// if content length is sent by client:
			// double check if the file was fully received
			// compare expected and actual size
			if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['REQUEST_METHOD'] !== 'LOCK') {
				$expected = $_SERVER['CONTENT_LENGTH'];
				if ($count != $expected) {
					$partStorage->unlink($internalPartPath);
					throw new BadRequest('expected filesize ' . $expected . ' got ' . $count);
				}
			}

		} catch (NotPermittedException $e) {
			// a more general case - due to whatever reason the content could not be written
			throw new Forbidden($e->getMessage());
		} catch (EntityTooLargeException $e) {
			// the file is too big to be stored
			throw new EntityTooLarge($e->getMessage());
		} catch (InvalidContentException $e) {
			// the file content is not permitted
			throw new UnsupportedMediaType($e->getMessage());
		} catch (InvalidPathException $e) {
			// the path for the file was not valid
			// TODO: find proper http status code for this case
			throw new Forbidden($e->getMessage());
		} catch (LockNotAcquiredException $e) {
			// the file is currently being written to by another process
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		} catch (GenericEncryptionException $e) {
			// returning 503 will allow retry of the operation at a later point in time
			throw new ServiceUnavailable("Encryption not ready: " . $e->getMessage());
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable("Failed to write file contents: " . $e->getMessage());
		}

		try {
			$view = \OC\Files\Filesystem::getView();
			$run = true;
			if ($view) {
				$hookPath = $view->getRelativePath($this->fileView->getAbsolutePath($this->path));

				if (!$exists) {
					\OC_Hook::emit(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_create, array(
						\OC\Files\Filesystem::signal_param_path => $hookPath,
						\OC\Files\Filesystem::signal_param_run => &$run,
					));
				} else {
					\OC_Hook::emit(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_update, array(
						\OC\Files\Filesystem::signal_param_path => $hookPath,
						\OC\Files\Filesystem::signal_param_run => &$run,
					));
				}
				\OC_Hook::emit(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_write, array(
					\OC\Files\Filesystem::signal_param_path => $hookPath,
					\OC\Files\Filesystem::signal_param_run => &$run,
				));
			}

			if ($needsPartFile) {
				// rename to correct path
				try {
					if ($run) {
						$renameOkay = $storage->moveFromStorage($partStorage, $internalPartPath, $internalPath);
						$fileExists = $storage->file_exists($internalPath);
					}
					if (!$run || $renameOkay === false || $fileExists === false) {
						\OC_Log::write('webdav', 'renaming part file to final file failed', \OC_Log::ERROR);
						$partStorage->unlink($internalPartPath);
						throw new Exception('Could not rename part file to final file');
					}
				} catch (\OCP\Files\LockNotAcquiredException $e) {
					// the file is currently being written to by another process
					throw new FileLocked($e->getMessage(), $e->getCode(), $e);
				}
			}

			// since we skipped the view we need to scan and emit the hooks ourselves
			$partStorage->getScanner()->scanFile($internalPath);

			if ($view) {
				$this->fileView->getUpdater()->propagate($hookPath);
				if (!$exists) {
					\OC_Hook::emit(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_post_create, array(
						\OC\Files\Filesystem::signal_param_path => $hookPath
					));
				} else {
					\OC_Hook::emit(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_post_update, array(
						\OC\Files\Filesystem::signal_param_path => $hookPath
					));
				}
				\OC_Hook::emit(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_post_write, array(
					\OC\Files\Filesystem::signal_param_path => $hookPath
				));
			}

			// allow sync clients to send the mtime along in a header
			$request = \OC::$server->getRequest();
			if (isset($request->server['HTTP_X_OC_MTIME'])) {
				if ($this->fileView->touch($this->path, $request->server['HTTP_X_OC_MTIME'])) {
					header('X-OC-MTime: accepted');
				}
			}
			$this->refreshInfo();
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable("Failed to check file size: " . $e->getMessage());
		}

		$this->fileView->unlockFile($this->path, ILockingProvider::LOCK_EXCLUSIVE);

		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Returns the data
	 *
	 * @return string|resource
	 * @throws Forbidden
	 * @throws ServiceUnavailable
	 */
	public function get() {

		//throw exception if encryption is disabled but files are still encrypted
		try {
			return $this->fileView->fopen(ltrim($this->path, '/'), 'rb');
		} catch (GenericEncryptionException $e) {
			// returning 503 will allow retry of the operation at a later point in time
			throw new ServiceUnavailable("Encryption not ready: " . $e->getMessage());
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable("Failed to open file: " . $e->getMessage());
		}
	}

	/**
	 * Delete the current file
	 *
	 * @throws Forbidden
	 * @throws ServiceUnavailable
	 */
	public function delete() {
		if (!$this->info->isDeletable()) {
			throw new Forbidden();
		}

		try {
			if (!$this->fileView->unlink($this->path)) {
				// assume it wasn't possible to delete due to permissions
				throw new Forbidden();
			}
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable("Failed to unlink: " . $e->getMessage());
		}
	}

	/**
	 * Returns the mime-type for a file
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return mixed
	 */
	public function getContentType() {
		$mimeType = $this->info->getMimetype();

		// PROPFIND needs to return the correct mime type, for consistency with the web UI
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PROPFIND') {
			return $mimeType;
		}
		return \OC_Helper::getSecureMimeType($mimeType);
	}

	/**
	 * @return array|false
	 */
	public function getDirectDownload() {
		if (\OCP\App::isEnabled('encryption')) {
			return [];
		}
		/** @var \OCP\Files\Storage $storage */
		list($storage, $internalPath) = $this->fileView->resolvePath($this->path);
		if (is_null($storage)) {
			return [];
		}

		return $storage->getDirectDownload($internalPath);
	}

	/**
	 * @param resource $data
	 * @return null|string
	 * @throws Exception
	 * @throws BadRequest
	 * @throws NotImplemented
	 * @throws ServiceUnavailable
	 */
	private function createFileChunked($data) {
		list($path, $name) = \Sabre\HTTP\URLUtil::splitPath($this->path);

		$info = \OC_FileChunking::decodeName($name);
		if (empty($info)) {
			throw new NotImplemented();
		}
		$chunk_handler = new \OC_FileChunking($info);
		$bytesWritten = $chunk_handler->store($info['index'], $data);

		//detect aborted upload
		if (isset ($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
			if (isset($_SERVER['CONTENT_LENGTH'])) {
				$expected = $_SERVER['CONTENT_LENGTH'];
				if ($bytesWritten != $expected) {
					$chunk_handler->remove($info['index']);
					throw new BadRequest(
						'expected filesize ' . $expected . ' got ' . $bytesWritten);
				}
			}
		}

		if ($chunk_handler->isComplete()) {
			list($storage,) = $this->fileView->resolvePath($path);
			$needsPartFile = $this->needsPartFile($storage);

			try {
				$targetPath = $path . '/' . $info['name'];
				if ($needsPartFile) {
					// we first assembly the target file as a part file
					$partFile = $path . '/' . $info['name'] . '.ocTransferId' . $info['transferid'] . '.part';
					$chunk_handler->file_assemble($partFile);

					// here is the final atomic rename
					$renameOkay = $this->fileView->rename($partFile, $targetPath);
					$fileExists = $this->fileView->file_exists($targetPath);
					if ($renameOkay === false || $fileExists === false) {
						\OC_Log::write('webdav', '\OC\Files\Filesystem::rename() failed', \OC_Log::ERROR);
						// only delete if an error occurred and the target file was already created
						if ($fileExists) {
							$this->fileView->unlink($targetPath);
						}
						throw new Exception('Could not rename part file assembled from chunks');
					}
				} else {
					// assemble directly into the final file
					$chunk_handler->file_assemble($targetPath);
				}

				// allow sync clients to send the mtime along in a header
				$request = \OC::$server->getRequest();
				if (isset($request->server['HTTP_X_OC_MTIME'])) {
					if ($this->fileView->touch($targetPath, $request->server['HTTP_X_OC_MTIME'])) {
						header('X-OC-MTime: accepted');
					}
				}

				$info = $this->fileView->getFileInfo($targetPath);
				return $info->getEtag();
			} catch (StorageNotAvailableException $e) {
				throw new ServiceUnavailable("Failed to put file: " . $e->getMessage());
			}
		}

		return null;
	}

	/**
	 * Returns whether a part file is needed for the given storage
	 * or whether the file can be assembled/uploaded directly on the
	 * target storage.
	 *
	 * @param \OCP\Files\Storage $storage
	 * @return bool true if the storage needs part file handling
	 */
	private function needsPartFile($storage) {
		// TODO: in the future use ChunkHandler provided by storage
		// and/or add method on Storage called "needsPartFile()"
		return !$storage->instanceOfStorage('OCA\Files_Sharing\External\Storage') &&
		!$storage->instanceOfStorage('OC\Files\Storage\OwnCloud');
	}
}
