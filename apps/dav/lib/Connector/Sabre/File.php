<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use Icewind\Streams\CallbackWrapper;
use OC\AppFramework\Http\Request;
use OC\Files\Filesystem;
use OC\Files\Stream\HashWrapper;
use OC\Files\View;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\Connector\Sabre\Exception\EntityTooLarge;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\Forbidden as DAVForbiddenException;
use OCA\DAV\Connector\Sabre\Exception\UnsupportedMediaType;
use OCP\App\IAppManager;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\Files;
use OCP\Files\EntityTooLargeException;
use OCP\Files\FileInfo;
use OCP\Files\ForbiddenException;
use OCP\Files\GenericFileException;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidContentException;
use OCP\Files\InvalidPathException;
use OCP\Files\LockNotAcquiredException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\IWriteStreamStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\IFile;

class File extends Node implements IFile {
	protected IRequest $request;
	protected IL10N $l10n;

	/**
	 * Sets up the node, expects a full path name
	 *
	 * @param View $view
	 * @param FileInfo $info
	 * @param ?\OCP\Share\IManager $shareManager
	 * @param ?IRequest $request
	 * @param ?IL10N $l10n
	 */
	public function __construct(View $view, FileInfo $info, ?IManager $shareManager = null, ?IRequest $request = null, ?IL10N $l10n = null) {
		parent::__construct($view, $info, $shareManager);

		if ($l10n) {
			$this->l10n = $l10n;
		} else {
			// Querying IL10N directly results in a dependency loop
			/** @var IL10NFactory $l10nFactory */
			$l10nFactory = Server::get(IL10NFactory::class);
			$this->l10n = $l10nFactory->get(Application::APP_ID);
		}

		if (isset($request)) {
			$this->request = $request;
		} else {
			$this->request = Server::get(IRequest::class);
		}
	}

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
	 * @param resource|string $data
	 *
	 * @throws Forbidden
	 * @throws UnsupportedMediaType
	 * @throws BadRequest
	 * @throws Exception
	 * @throws EntityTooLarge
	 * @throws ServiceUnavailable
	 * @throws FileLocked
	 * @return string|null
	 */
	public function put($data) {
		try {
			$exists = $this->fileView->file_exists($this->path);
			if ($exists && !$this->info->isUpdateable()) {
				throw new Forbidden();
			}
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($this->l10n->t('File is not updatable: %1$s', [$e->getMessage()]));
		}

		// verify path of the target
		$this->verifyPath();

		[$partStorage] = $this->fileView->resolvePath($this->path);
		if ($partStorage === null) {
			throw new ServiceUnavailable($this->l10n->t('Failed to get storage for file'));
		}
		$needsPartFile = $partStorage->needsPartFile() && (strlen($this->path) > 1);

		$view = Filesystem::getView();

		if ($needsPartFile) {
			$transferId = \rand();
			// mark file as partial while uploading (ignored by the scanner)
			$partFilePath = $this->getPartFileBasePath($this->path) . '.ocTransferId' . $transferId . '.part';

			if (!$view->isCreatable($partFilePath) && $view->isUpdatable($this->path)) {
				$needsPartFile = false;
			}
		}
		if (!$needsPartFile) {
			// upload file directly as the final path
			$partFilePath = $this->path;

			if ($view && !$this->emitPreHooks($exists)) {
				throw new Exception($this->l10n->t('Could not write to final file, canceled by hook'));
			}
		}

		// the part file and target file might be on a different storage in case of a single file storage (e.g. single file share)
		[$partStorage, $internalPartPath] = $this->fileView->resolvePath($partFilePath);
		[$storage, $internalPath] = $this->fileView->resolvePath($this->path);
		if ($partStorage === null || $storage === null) {
			throw new ServiceUnavailable($this->l10n->t('Failed to get storage for file'));
		}
		try {
			if (!$needsPartFile) {
				try {
					$this->changeLock(ILockingProvider::LOCK_EXCLUSIVE);
				} catch (LockedException $e) {
					// during very large uploads, the shared lock we got at the start might have been expired
					// meaning that the above lock can fail not just only because somebody else got a shared lock
					// or because there is no existing shared lock to make exclusive
					//
					// Thus we try to get a new exclusive lock, if the original lock failed because of a different shared
					// lock this will still fail, if our original shared lock expired the new lock will be successful and
					// the entire operation will be safe

					try {
						$this->acquireLock(ILockingProvider::LOCK_EXCLUSIVE);
					} catch (LockedException $ex) {
						throw new FileLocked($e->getMessage(), $e->getCode(), $e);
					}
				}
			}

			if (!is_resource($data)) {
				$tmpData = fopen('php://temp', 'r+');
				if ($data !== null) {
					fwrite($tmpData, $data);
					rewind($tmpData);
				}
				$data = $tmpData;
			}

			if ($this->request->getHeader('X-HASH') !== '') {
				$hash = $this->request->getHeader('X-HASH');
				if ($hash === 'all' || $hash === 'md5') {
					$data = HashWrapper::wrap($data, 'md5', function ($hash): void {
						$this->header('X-Hash-MD5: ' . $hash);
					});
				}

				if ($hash === 'all' || $hash === 'sha1') {
					$data = HashWrapper::wrap($data, 'sha1', function ($hash): void {
						$this->header('X-Hash-SHA1: ' . $hash);
					});
				}

				if ($hash === 'all' || $hash === 'sha256') {
					$data = HashWrapper::wrap($data, 'sha256', function ($hash): void {
						$this->header('X-Hash-SHA256: ' . $hash);
					});
				}
			}

			if ($partStorage->instanceOfStorage(IWriteStreamStorage::class)) {
				$isEOF = false;
				$wrappedData = CallbackWrapper::wrap($data, null, null, null, null, function ($stream) use (&$isEOF): void {
					$isEOF = feof($stream);
				});

				$result = is_resource($wrappedData);
				if ($result) {
					$count = -1;
					try {
						/** @var IWriteStreamStorage $partStorage */
						$count = $partStorage->writeStream($internalPartPath, $wrappedData);
					} catch (GenericFileException $e) {
						$logger = Server::get(LoggerInterface::class);
						$logger->error('Error while writing stream to storage: ' . $e->getMessage(), ['exception' => $e, 'app' => 'webdav']);
						$result = $isEOF;
						if (is_resource($wrappedData)) {
							$result = feof($wrappedData);
						}
					}
				}
			} else {
				$target = $partStorage->fopen($internalPartPath, 'wb');
				if ($target === false) {
					Server::get(LoggerInterface::class)->error('\OC\Files\Filesystem::fopen() failed', ['app' => 'webdav']);
					// because we have no clue about the cause we can only throw back a 500/Internal Server Error
					throw new Exception($this->l10n->t('Could not write file contents'));
				}
				[$count, $result] = Files::streamCopy($data, $target, true);
				fclose($target);
			}

			$lengthHeader = $this->request->getHeader('content-length');
			$expected = $lengthHeader !== '' ? (int)$lengthHeader : -1;
			if ($result === false && $expected >= 0) {
				throw new Exception(
					$this->l10n->t(
						'Error while copying file to target location (copied: %1$s, expected filesize: %2$s)',
						[
							$this->l10n->n('%n byte', '%n bytes', $count),
							$this->l10n->n('%n byte', '%n bytes', $expected),
						],
					)
				);
			}

			// if content length is sent by client:
			// double check if the file was fully received
			// compare expected and actual size
			if ($expected >= 0
				&& $expected !== $count
				&& $this->request->getMethod() === 'PUT'
			) {
				throw new BadRequest(
					$this->l10n->t(
						'Expected filesize of %1$s but read (from Nextcloud client) and wrote (to Nextcloud storage) %2$s. Could either be a network problem on the sending side or a problem writing to the storage on the server side.',
						[
							$this->l10n->n('%n byte', '%n bytes', $expected),
							$this->l10n->n('%n byte', '%n bytes', $count),
						],
					)
				);
			}
		} catch (\Exception $e) {
			if ($e instanceof LockedException) {
				Server::get(LoggerInterface::class)->debug($e->getMessage(), ['exception' => $e]);
			} else {
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
			}

			if ($needsPartFile) {
				$partStorage->unlink($internalPartPath);
			}
			$this->convertToSabreException($e);
		}

		try {
			if ($needsPartFile) {
				if ($view && !$this->emitPreHooks($exists)) {
					$partStorage->unlink($internalPartPath);
					throw new Exception($this->l10n->t('Could not rename part file to final file, canceled by hook'));
				}
				try {
					$this->changeLock(ILockingProvider::LOCK_EXCLUSIVE);
				} catch (LockedException $e) {
					// during very large uploads, the shared lock we got at the start might have been expired
					// meaning that the above lock can fail not just only because somebody else got a shared lock
					// or because there is no existing shared lock to make exclusive
					//
					// Thus we try to get a new exclusive lock, if the original lock failed because of a different shared
					// lock this will still fail, if our original shared lock expired the new lock will be successful and
					// the entire operation will be safe

					try {
						$this->acquireLock(ILockingProvider::LOCK_EXCLUSIVE);
					} catch (LockedException $ex) {
						if ($needsPartFile) {
							$partStorage->unlink($internalPartPath);
						}
						throw new FileLocked($e->getMessage(), $e->getCode(), $e);
					}
				}

				// rename to correct path
				try {
					$renameOkay = $storage->moveFromStorage($partStorage, $internalPartPath, $internalPath);
					$fileExists = $storage->file_exists($internalPath);
					if ($renameOkay === false || $fileExists === false) {
						Server::get(LoggerInterface::class)->error('renaming part file to final file failed $renameOkay: ' . ($renameOkay ? 'true' : 'false') . ', $fileExists: ' . ($fileExists ? 'true' : 'false') . ')', ['app' => 'webdav']);
						throw new Exception($this->l10n->t('Could not rename part file to final file'));
					}
				} catch (ForbiddenException $ex) {
					if (!$ex->getRetry()) {
						$partStorage->unlink($internalPartPath);
					}
					throw new DAVForbiddenException($ex->getMessage(), $ex->getRetry());
				} catch (\Exception $e) {
					$partStorage->unlink($internalPartPath);
					$this->convertToSabreException($e);
				}
			}

			// since we skipped the view we need to scan and emit the hooks ourselves
			$storage->getUpdater()->update($internalPath);

			try {
				$this->changeLock(ILockingProvider::LOCK_SHARED);
			} catch (LockedException $e) {
				throw new FileLocked($e->getMessage(), $e->getCode(), $e);
			}

			// allow sync clients to send the mtime along in a header
			$mtimeHeader = $this->request->getHeader('x-oc-mtime');
			if ($mtimeHeader !== '') {
				$mtime = $this->sanitizeMtime($mtimeHeader);
				if ($this->fileView->touch($this->path, $mtime)) {
					$this->header('X-OC-MTime: accepted');
				}
			}

			$fileInfoUpdate = [
				'upload_time' => time()
			];

			// allow sync clients to send the creation time along in a header
			$ctimeHeader = $this->request->getHeader('x-oc-ctime');
			if ($ctimeHeader) {
				$ctime = $this->sanitizeMtime($ctimeHeader);
				$fileInfoUpdate['creation_time'] = $ctime;
				$this->header('X-OC-CTime: accepted');
			}

			$this->fileView->putFileInfo($this->path, $fileInfoUpdate);

			if ($view) {
				$this->emitPostHooks($exists);
			}

			$this->refreshInfo();

			$checksumHeader = $this->request->getHeader('oc-checksum');
			if ($checksumHeader) {
				$checksum = trim($checksumHeader);
				$this->setChecksum($checksum);
			} elseif ($this->getChecksum() !== null && $this->getChecksum() !== '') {
				$this->setChecksum('');
			}
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($this->l10n->t('Failed to check file size: %1$s', [$e->getMessage()]), 0, $e);
		}

		return '"' . $this->info->getEtag() . '"';
	}

	private function getPartFileBasePath($path) {
		$partFileInStorage = Server::get(IConfig::class)->getSystemValue('part_file_in_storage', true);
		if ($partFileInStorage) {
			$filename = basename($path);
			// hash does not need to be secure but fast and semi unique
			$hashedFilename = hash('xxh128', $filename);
			return substr($path, 0, strlen($path) - strlen($filename)) . $hashedFilename;
		} else {
			// will place the .part file in the users root directory
			// therefor we need to make the name (semi) unique - hash does not need to be secure but fast.
			return hash('xxh128', $path);
		}
	}

	private function emitPreHooks(bool $exists, ?string $path = null): bool {
		if (is_null($path)) {
			$path = $this->path;
		}
		$hookPath = Filesystem::getView()->getRelativePath($this->fileView->getAbsolutePath($path));
		if ($hookPath === null) {
			// We only trigger hooks from inside default view
			return true;
		}
		$run = true;

		if (!$exists) {
			\OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_create, [
				Filesystem::signal_param_path => $hookPath,
				Filesystem::signal_param_run => &$run,
			]);
		} else {
			\OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_update, [
				Filesystem::signal_param_path => $hookPath,
				Filesystem::signal_param_run => &$run,
			]);
		}
		\OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_write, [
			Filesystem::signal_param_path => $hookPath,
			Filesystem::signal_param_run => &$run,
		]);
		return $run;
	}

	private function emitPostHooks(bool $exists, ?string $path = null): void {
		if (is_null($path)) {
			$path = $this->path;
		}
		$hookPath = Filesystem::getView()->getRelativePath($this->fileView->getAbsolutePath($path));
		if ($hookPath === null) {
			// We only trigger hooks from inside default view
			return;
		}
		if (!$exists) {
			\OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_post_create, [
				Filesystem::signal_param_path => $hookPath
			]);
		} else {
			\OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_post_update, [
				Filesystem::signal_param_path => $hookPath
			]);
		}
		\OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_post_write, [
			Filesystem::signal_param_path => $hookPath
		]);
	}

	/**
	 * Returns the data
	 *
	 * @return resource
	 * @throws Forbidden
	 * @throws ServiceUnavailable
	 */
	public function get() {
		//throw exception if encryption is disabled but files are still encrypted
		try {
			if (!$this->info->isReadable()) {
				// do a if the file did not exist
				throw new NotFound();
			}
			$path = ltrim($this->path, '/');
			try {
				$res = $this->fileView->fopen($path, 'rb');
			} catch (\Exception $e) {
				$this->convertToSabreException($e);
			}

			if ($res === false) {
				if ($this->fileView->file_exists($path)) {
					throw new ServiceUnavailable($this->l10n->t('Could not open file: %1$s, file does seem to exist', [$path]));
				} else {
					throw new ServiceUnavailable($this->l10n->t('Could not open file: %1$s, file doesn\'t seem to exist', [$path]));
				}
			}

			// comparing current file size with the one in DB
			// if different, fix DB and refresh cache.
			if ($this->getSize() !== $this->fileView->filesize($this->getPath())) {
				$logger = Server::get(LoggerInterface::class);
				$logger->warning('fixing cached size of file id=' . $this->getId());

				$this->getFileInfo()->getStorage()->getUpdater()->update($this->getFileInfo()->getInternalPath());
				$this->refreshInfo();
			}

			return $res;
		} catch (GenericEncryptionException $e) {
			// returning 503 will allow retry of the operation at a later point in time
			throw new ServiceUnavailable($this->l10n->t('Encryption not ready: %1$s', [$e->getMessage()]));
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($this->l10n->t('Failed to open file: %1$s', [$e->getMessage()]));
		} catch (ForbiddenException $ex) {
			throw new DAVForbiddenException($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
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
			throw new ServiceUnavailable($this->l10n->t('Failed to unlink: %1$s', [$e->getMessage()]));
		} catch (ForbiddenException $ex) {
			throw new DAVForbiddenException($ex->getMessage(), $ex->getRetry());
		} catch (LockedException $e) {
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Returns the mime-type for a file
	 *
	 * If null is returned, we'll assume application/octet-stream
	 *
	 * @return string
	 */
	public function getContentType() {
		$mimeType = $this->info->getMimetype();

		// PROPFIND needs to return the correct mime type, for consistency with the web UI
		if ($this->request->getMethod() === 'PROPFIND') {
			return $mimeType;
		}
		return Server::get(IMimeTypeDetector::class)->getSecureMimeType($mimeType);
	}

	/**
	 * @return array|bool
	 */
	public function getDirectDownload() {
		if (Server::get(IAppManager::class)->isEnabledForUser('encryption')) {
			return [];
		}
		[$storage, $internalPath] = $this->fileView->resolvePath($this->path);
		if (is_null($storage)) {
			return [];
		}

		return $storage->getDirectDownload($internalPath);
	}

	/**
	 * Convert the given exception to a SabreException instance
	 *
	 * @param \Exception $e
	 *
	 * @throws \Sabre\DAV\Exception
	 */
	private function convertToSabreException(\Exception $e) {
		if ($e instanceof \Sabre\DAV\Exception) {
			throw $e;
		}
		if ($e instanceof NotPermittedException) {
			// a more general case - due to whatever reason the content could not be written
			throw new Forbidden($e->getMessage(), 0, $e);
		}
		if ($e instanceof ForbiddenException) {
			// the path for the file was forbidden
			throw new DAVForbiddenException($e->getMessage(), $e->getRetry(), $e);
		}
		if ($e instanceof EntityTooLargeException) {
			// the file is too big to be stored
			throw new EntityTooLarge($e->getMessage(), 0, $e);
		}
		if ($e instanceof InvalidContentException) {
			// the file content is not permitted
			throw new UnsupportedMediaType($e->getMessage(), 0, $e);
		}
		if ($e instanceof InvalidPathException) {
			// the path for the file was not valid
			// TODO: find proper http status code for this case
			throw new Forbidden($e->getMessage(), 0, $e);
		}
		if ($e instanceof LockedException || $e instanceof LockNotAcquiredException) {
			// the file is currently being written to by another process
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
		if ($e instanceof GenericEncryptionException) {
			// returning 503 will allow retry of the operation at a later point in time
			throw new ServiceUnavailable($this->l10n->t('Encryption not ready: %1$s', [$e->getMessage()]), 0, $e);
		}
		if ($e instanceof StorageNotAvailableException) {
			throw new ServiceUnavailable($this->l10n->t('Failed to write file contents: %1$s', [$e->getMessage()]), 0, $e);
		}
		if ($e instanceof NotFoundException) {
			throw new NotFound($this->l10n->t('File not found: %1$s', [$e->getMessage()]), 0, $e);
		}

		throw new \Sabre\DAV\Exception($e->getMessage(), 0, $e);
	}

	/**
	 * Get the checksum for this file
	 *
	 * @return string|null
	 */
	public function getChecksum() {
		return $this->info->getChecksum();
	}

	public function setChecksum(string $checksum) {
		$this->fileView->putFileInfo($this->path, ['checksum' => $checksum]);
		$this->refreshInfo();
	}

	protected function header($string) {
		if (!\OC::$CLI) {
			\header($string);
		}
	}

	public function hash(string $type) {
		return $this->fileView->hash($type, $this->path);
	}

	public function getNode(): \OCP\Files\File {
		return $this->node;
	}
}
