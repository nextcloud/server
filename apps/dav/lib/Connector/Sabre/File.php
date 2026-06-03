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
use OCP\Constants;
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
use OCP\Files\NotEnoughSpaceException;
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
	 * Write or replace the file contents from a stream or string payload.
	 *
	 * Depending on the storage backend, the upload is written either directly to
	 * the target path or to a temporary part file that is renamed into place after
	 * the write completed successfully.
	 *
	 * In addition to writing the payload, this method validates the target path,
	 * manages upload locks, verifies the written size, emits filesystem hooks,
	 * updates file metadata, and translates storage exceptions to DAV exceptions.
	 *
	 * @param resource|string|null $data Readable stream or full file contents.
	 *
	 * @throws Forbidden
	 * @throws UnsupportedMediaType
	 * @throws BadRequest
	 * @throws Exception
	 * @throws EntityTooLarge
	 * @throws ServiceUnavailable
	 * @throws FileLocked
	 * @return string|null ETag surrounded by double quotes on success.
	 */
	#[\Override]
	public function put($data) {
		// 1. Validate the target and choose whether to write directly or through a
		// temporary part file.
		try {
			$targetExists = $this->fileView->file_exists($this->path);
			if ($targetExists && !$this->info->isUpdateable()) {
				throw new Forbidden();
			}
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($this->l10n->t('File is not updatable: %1$s', [$e->getMessage()]));
		}

		// Validate the target path before resolving storages or opening streams.
		$this->verifyPath();
		$defaultView = Filesystem::getView();

		[
			'usePartFile' => $usePartFile,
			'uploadPath' => $uploadPath,
		] = $this->resolveUploadTarget($targetExists, $defaultView);

		// The temporary upload file and final target may live on different storages,
		// for example when writing through a single-file share.
		[$partStorage, $partInternalPath] = $this->fileView->resolvePath($uploadPath);
		[$targetStorage, $targetInternalPath] = $this->fileView->resolvePath($this->path);

		if ($partStorage === null || $targetStorage === null) {
			throw new ServiceUnavailable($this->l10n->t('Failed to get storage for file'));
		}

		// 2. Stream the request body to storage.
		try {
			if (!$usePartFile) {
				$this->acquireExclusiveLockForWrite();
			}

			$stream = $this->normalizePutData($data);
			$stream = $this->wrapStreamWithRequestedHashes($stream);
			$expectedSize = $this->getExpectedSize();

			$writeResult = $this->writePutDataToStorage(
				$partStorage,
				$partInternalPath,
				$stream,
				$expectedSize,
			);

			$this->validateWrittenSize(
				$writeResult['success'],
				$writeResult['bytesWritten'],
				$expectedSize,
			);
	
		} catch (\Exception $e) {
			if ($e instanceof LockedException) {
				Server::get(LoggerInterface::class)->debug($e->getMessage(), ['exception' => $e]);
			} else {
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
			}

			if ($usePartFile) {
				$partStorage->unlink($partInternalPath);
			}
			$this->convertToSabreException($e);
		}

		// 3. Finalize the upload, refresh metadata, and emit hooks.
		try {
			if ($usePartFile) {
				$this->finalizePartFileUpload(
					$partStorage,
					$partInternalPath,
					$targetStorage,
					$targetInternalPath,
					$defaultView,
					$targetExists,
				);
			}

			// Refresh storage metadata because the write path bypassed the usual view logic.
			$targetStorage->getUpdater()->update($targetInternalPath);

			// Downgrade back to a shared lock after the write/rename completed.
			try {
				$this->changeLock(ILockingProvider::LOCK_SHARED);
			} catch (LockedException $e) {
				throw new FileLocked($e->getMessage(), $e->getCode(), $e);
			}

			$this->applyUploadMetadata();

			if ($defaultView) {
				// Emit post-write hooks explicitly because the write path bypassed the usual view logic.
				$this->emitPostHooks($targetExists);
			}

			$this->refreshInfo();

			$this->applyUploadChecksum();
		} catch (StorageNotAvailableException $e) {
			throw new ServiceUnavailable($this->l10n->t('Failed to check file size: %1$s', [$e->getMessage()]), 0, $e);
		}

		return '"' . $this->info->getEtag() . '"';
	}

	private function acquireExclusiveLockForWrite(): void {
		try {
			$this->changeLock(ILockingProvider::LOCK_EXCLUSIVE);
		} catch (LockedException $e) {
			// For very large uploads the original shared lock may already have expired.
			// In that case upgrading it can fail even when there is no competing writer.
			// Retry by acquiring a fresh exclusive lock directly.			
			try {
				$this->acquireLock(ILockingProvider::LOCK_EXCLUSIVE);
			} catch (LockedException $ex) {
				throw new FileLocked($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * @param resource|string|null $data
	 * @return resource
	 */
	private function normalizePutData($data) {
		if (is_resource($data)) {
			return $data;
		}

		$stream = fopen('php://temp', 'r+');
		if ($data !== null) {
			fwrite($stream, $data);
			rewind($stream);
		}

		return $stream;
	}

	/**
	 * @param resource $stream
	 * @return resource
	 */
	private function wrapStreamWithRequestedHashes($stream) {
		$requestedHash = $this->request->getHeader('X-HASH');
		if ($requestedHash === '') {
			return $stream;
		}

		if ($requestedHash === 'all' || $requestedHash === 'md5') {
			$stream = HashWrapper::wrap($stream, 'md5', function ($hash): void {
				$this->header('X-Hash-MD5: ' . $hash);
			});
		}

		if ($requestedHash === 'all' || $requestedHash === 'sha1') {
			$stream = HashWrapper::wrap($stream, 'sha1', function ($hash): void {
				$this->header('X-Hash-SHA1: ' . $hash);
			});
		}

		if ($requestedHash === 'all' || $requestedHash === 'sha256') {
			$stream = HashWrapper::wrap($stream, 'sha256', function ($hash): void {
				$this->header('X-Hash-SHA256: ' . $hash);
			});
		}

		return $stream;
	}

	private function getExpectedSize(): ?int {
		$contentLength = $this->request->getHeader('content-length');
		return $contentLength !== '' ? (int)$contentLength : null;
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

	/**
	 * Decide whether the upload should write directly to the final target or to a
	 * temporary part file first.
	 *
	 * For direct writes, pre-write hooks are emitted immediately before the target
	 * is modified. Part-file uploads defer those hooks until just before the final
	 * rename into place.
	 *
	 * @return array{usePartFile: bool, uploadPath: string, partStorage: mixed}
	 */
	private function resolveUploadTarget(bool $targetExists, View $defaultView): array {
		[$initialStorage] = $this->fileView->resolvePath($this->path);
		if ($initialStorage === null) {
			throw new ServiceUnavailable($this->l10n->t('Failed to get storage for file'));
		}

		$usePartFile = $initialStorage->needsPartFile() && (strlen($this->path) > 1);

		if ($usePartFile) {
			$transferId = \rand();
			// Use a temporary .part file while the upload is in progress.
			// Scanner logic ignores these partial files.
			$uploadPath = $this->getPartFileBasePath($this->path) . '.ocTransferId' . $transferId . '.part';

			if (!$defaultView->isCreatable($uploadPath) && $defaultView->isUpdatable($this->path)) {
				$usePartFile = false;
			}
		}

		if (!$usePartFile) {
			// Write directly to the final target path instead of using a temporary part file.
			$uploadPath = $this->path;

			// For direct writes, run pre-write hooks before touching the final target.
			// Part-file uploads defer these hooks until just before the final rename.
			if (!$this->emitPreHooks($targetExists)) {
				throw new Exception($this->l10n->t('Could not write to final file, canceled by hook'));
			}
		}

		return [
			'usePartFile' => $usePartFile,
			'uploadPath' => $uploadPath,
			'partStorage' => $initialStorage,
		];
	}

	/**
	 * @param resource $stream
	 * @return array{success: bool, bytesWritten: int}
	 */
	private function writePutDataToStorage(
		$partStorage,
		string $partInternalPath,
		$stream,
		?int $expectedSize,
	): array {
		if ($partStorage->instanceOfStorage(IWriteStreamStorage::class)) {
			$isEOF = false;
			$wrappedStream = CallbackWrapper::wrap($stream, null, null, null, null, function ($stream) use (&$isEOF): void {
				$isEOF = feof($stream);
			});

			$writeSucceeded = is_resource($wrappedStream);
			$bytesWritten = -1;

			if ($writeSucceeded) {
				try {
					/** @var IWriteStreamStorage $partStorage */
					$bytesWritten = $partStorage->writeStream($partInternalPath, $wrappedStream, $expectedSize);
				} catch (GenericFileException $e) {
					Server::get(LoggerInterface::class)->error(
						'Error while writing stream to storage: ' . $e->getMessage(),
						['exception' => $e, 'app' => 'webdav']
					);
					$writeSucceeded = $isEOF;
					if (is_resource($wrappedStream)) {
						$writeSucceeded = feof($wrappedStream);
					}
				}
			}

			return [
				'success' => $writeSucceeded,
				'bytesWritten' => $bytesWritten,
			];
		}

		$targetStream = $partStorage->fopen($partInternalPath, 'wb');
		if ($targetStream === false) {
			Server::get(LoggerInterface::class)->error('\OC\Files\Filesystem::fopen() failed', ['app' => 'webdav']);
			// fopen() did not provide a more specific failure reason, so surface this as
			// a generic DAV error.
			throw new Exception($this->l10n->t('Could not write file contents'));
		}

		$bytesWritten = stream_copy_to_stream($stream, $targetStream);
		fclose($targetStream);

		if ($bytesWritten === false) {
			return [
				'success' => false,
				'bytesWritten' => 0,
			];
		}

		return [
			'success' => true,
			'bytesWritten' => $bytesWritten,
		];
	}

	private function validateWrittenSize(bool $writeSucceeded, int $bytesWritten, ?int $expectedSize): void {
		if ($writeSucceeded === false && $expectedSize !== null) {
			throw new Exception(
				$this->l10n->t(
					'Error while copying file to target location (copied: %1$s, expected filesize: %2$s)',
					[
						$this->l10n->n('%n byte', '%n bytes', $bytesWritten),
						$this->l10n->n('%n byte', '%n bytes', $expectedSize),
					],
				)
			);
		}

		// When the client sends Content-Length for a PUT request, verify that the
		// number of bytes written matches the announced payload size.
		if ($expectedSize !== null
			&& $expectedSize !== $bytesWritten
			&& $this->request->getMethod() === 'PUT'
		) {
			throw new BadRequest(
				$this->l10n->t(
					'Expected filesize of %1$s but read (from Nextcloud client) and wrote (to Nextcloud storage) %2$s. Could either be a network problem on the sending side or a problem writing to the storage on the server side.',
					[
						$this->l10n->n('%n byte', '%n bytes', $expectedSize),
						$this->l10n->n('%n byte', '%n bytes', $bytesWritten),
					],
				)
			);
		}
	}

	private function applyUploadMetadata(): void {
		// Accept client-provided timestamps used by sync clients to preserve metadata.
		$mtimeHeader = $this->request->getHeader('x-oc-mtime');
		if ($mtimeHeader !== '') {
			$mtime = $this->sanitizeMtime($mtimeHeader);
			if ($this->fileView->touch($this->path, $mtime)) {
				$this->header('X-OC-MTime: accepted');
			}
		}

		$fileInfoUpdates = [
			'upload_time' => time(),
		];

		$ctimeHeader = $this->request->getHeader('x-oc-ctime');
		if ($ctimeHeader) {
			$ctime = $this->sanitizeMtime($ctimeHeader);
			$fileInfoUpdates['creation_time'] = $ctime;
			$this->header('X-OC-CTime: accepted');
		}

		$this->fileView->putFileInfo($this->path, $fileInfoUpdates);
	}

	private function applyUploadChecksum(): void {
		// Persist the checksum provided by the client. If none was sent, clear any
		// previously stored checksum so metadata reflects the current upload.
		$checksumHeader = $this->request->getHeader('oc-checksum');
		if ($checksumHeader) {
			$checksum = trim($checksumHeader);
			$this->setChecksum($checksum);
		} elseif ($this->getChecksum() !== null && $this->getChecksum() !== '') {
			$this->setChecksum('');
		}
	}

	private function finalizePartFileUpload(
		$partStorage,
		string $partInternalPath,
		$targetStorage,
		string $targetInternalPath,
		$defaultView,
		bool $targetExists,
	): void {
		if ($defaultView && !$this->emitPreHooks($targetExists)) {
			$partStorage->unlink($partInternalPath);
			throw new Exception($this->l10n->t('Could not rename part file to final file, canceled by hook'));
		}

		$this->acquireExclusiveLockForWrite();

		try {
			$renameSucceeded = $targetStorage->moveFromStorage($partStorage, $partInternalPath, $targetInternalPath);
			$targetExistsAfterRename = $targetStorage->file_exists($targetInternalPath);

			if ($renameSucceeded === false || $targetExistsAfterRename === false) {
				Server::get(LoggerInterface::class)->error(
					'renaming part file to final file failed $renameSucceeded: '
					. ($renameSucceeded ? 'true' : 'false')
					. ', $targetExistsAfterRename: '
					. ($targetExistsAfterRename ? 'true' : 'false')
					. ')', ['app' => 'webdav']
				);
				throw new Exception($this->l10n->t('Could not rename part file to final file'));
			}
		} catch (ForbiddenException $ex) {
			if (!$ex->getRetry()) {
				$partStorage->unlink($partInternalPath);
			}
			throw new DAVForbiddenException($ex->getMessage(), $ex->getRetry());
		} catch (\Exception $e) {
			$partStorage->unlink($partInternalPath);
			$this->convertToSabreException($e);
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
	#[\Override]
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
					throw new ServiceUnavailable($this->l10n->t('Could not open file: %1$s (%2$d), file does seem to exist', [$path, $this->info->getId()]));
				} else {
					throw new ServiceUnavailable($this->l10n->t('Could not open file: %1$s (%2$d), file doesn\'t seem to exist', [$path, $this->info->getId()]));
				}
			}

			$logger = Server::get(LoggerInterface::class);
			// comparing current file size with the one in DB
			// if different, fix DB and refresh cache.
			//
			$fsSize = $this->fileView->filesize($this->getPath());
			if ($fsSize === false) {
				$logger->warning('file not found on storage after successfully opening it');
				throw new ServiceUnavailable($this->l10n->t('Failed to get size for : %1$s', [$this->getPath()]));
			} elseif ($this->getSize() !== $fsSize) {
				$logger->warning('fixing cached size of file id=' . $this->getId() . ', cached size was ' . $this->getSize() . ', but the filesystem reported a size of ' . $fsSize);

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
	#[\Override]
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
	#[\Override]
	public function getContentType() {
		$mimeType = $this->info->getMimetype();

		// PROPFIND needs to return the correct mime type, for consistency with the web UI
		if ($this->request->getMethod() === 'PROPFIND') {
			return $mimeType;
		}
		return Server::get(IMimeTypeDetector::class)->getSecureMimeType($mimeType);
	}

	/**
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getDirectDownload(): array|false {
		if (Server::get(IAppManager::class)->isEnabledForUser('encryption')) {
			return false;
		}
		$node = $this->getNode();
		$storage = $node->getStorage();
		if (!$storage) {
			return false;
		}

		if (!($node->getPermissions() & Constants::PERMISSION_READ)) {
			return false;
		}

		return $storage->getDirectDownloadById((string)$node->getId());
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
		if ($e instanceof NotEnoughSpaceException) {
			throw new EntityTooLarge($this->l10n->t('Insufficient space'), 0, $e);
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

	#[\Override]
	public function getNode(): \OCP\Files\File {
		return $this->node;
	}
}
