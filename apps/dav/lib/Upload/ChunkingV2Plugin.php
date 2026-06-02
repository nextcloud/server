<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Upload;

use Exception;
use InvalidArgumentException;
use OC\Files\Filesystem;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\View;
use OC\Memcache\Memcached;
use OC\Memcache\Redis;
use OC_Hook;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCP\AppFramework\Http;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\ObjectStore\IObjectStoreMultiPartUpload;
use OCP\Files\Storage\IChunkedFileWrite;
use OCP\Files\StorageInvalidException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

/**
 * Sabre server plugin that coordinates chunked WebDAV uploads and finalization.
 *
 * Uploads are written either directly to the target file or to a staging file
 * in the upload folder, depending on the target storage backend.
 */
class ChunkingV2Plugin extends ServerPlugin {
	private Server $server;
	private UploadFolder $uploadFolder;
	private ICache $cache;
	private ?string $uploadId = null;
	private ?string $uploadPath = null;

	private const TEMP_TARGET = '.target';
	private const DESTINATION_HEADER = 'Destination';

	public const CACHE_KEY = 'chunking-v2';
	public const UPLOAD_TARGET_PATH = 'upload-target-path';
	public const UPLOAD_TARGET_ID = 'upload-target-id';
	public const UPLOAD_ID = 'upload-id';

	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->createDistributed(self::CACHE_KEY);
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function initialize(Server $server) {
		$server->on('beforeMethod:GET', $this->beforeGet(...));
		$server->on('beforeMethod:PUT', [$this, 'beforePut']);
		$server->on('beforeMethod:DELETE', [$this, 'beforeDelete']);
		$server->on('beforeMove', [$this, 'beforeMove'], 90);
		$server->on('afterMethod:MKCOL', [$this, 'afterMkcol']);

		$this->server = $server;
	}

	protected function beforeGet(RequestInterface $request) {
		$sourceNode = $this->server->tree->getNodeForPath($request->getPath());
		if (($sourceNode instanceof FutureFile) || ($sourceNode instanceof UploadFile)) {
			throw new MethodNotAllowed('Reading intermediate uploads is not allowed');
		}

		return true;
	}

	/**
	 * Resolve the file and storage/path tuple used for chunk writes.
	 *
	 * Direct writes are only used when the destination file already exists on the
	 * same storage as the upload folder. Otherwise chunks are written to the
	 * staging file in the upload folder and finalized with copy/move afterwards.
	 *
	 * FIXME: Verify 'file' return type; old code had probably overly broad `@return FutureFile|UploadFile|ICollection|INode`
	 *
	 * @return array{
	 *   file: File,
	 *   storage: \OCP\Files\Storage\IStorage,
	 *   storagePath: string,
	 *   isDirect: bool
	 * }
	 */
	private function resolveChunkWriteTarget(string $path, bool $createIfNotExists = false): array {
		if (!$this->uploadFolder instanceof UploadFolder) {
			throw new \LogicException('Upload folder not initialized');
		}

		$directTarget = $this->tryResolveDirectChunkWriteTarget($path);
		if ($directTarget !== null) {
			return $directTarget;
		}

		return $this->resolveStagingChunkWriteTarget($createIfNotExists);
	}

	/**
	 * Try to resolve the destination as a direct chunk-write target.
	 *
	 * This only succeeds when the destination exists as a file and is backed by
	 * the same storage as the upload folder.
	 *
	 * @return array{
	 *   file: File,
	 *   storage: \OCP\Files\Storage\IStorage,
	 *   storagePath: string,
	 *   isDirect: bool
	 * }|null
	 */
	private function tryResolveDirectChunkWriteTarget(string $path): ?array {
		$uploadStorage = $this->uploadFolder->getStorage();

		try {
			$actualFile = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			// No target file exists yet; fall back to staging.
			return null;
		}

		if (!$actualFile instanceof File) {
			return null;
		}

		$actualNode = $actualFile->getNode();
		// This is a conservative optimization: only bypass staging when the
		// destination already lives on the same storage as the upload folder.
		// Broader direct-write support may be possible, but only if this
		// resolver is extended to return the correct storage/path pair for
		// that backend.
		if ($uploadStorage->getId() !== $actualNode->getStorage()->getId()) {
			return null;
		}

		return [
			'file' => $actualFile,
			'storage' => $uploadStorage,
			'storagePath' => $actualFile->getInternalPath(),
			'isDirect' => true,
		];
	}

	/**
	 * Resolve the staging file used when direct chunk writes are not possible.
	 *
	 * The staging file lives in the upload folder and is copied or moved to the
	 * final destination after the chunked write has been completed.
	 *
	 * @return array{
	 *   file: File,
	 *   storage: \OCP\Files\Storage\IStorage,
	 *   storagePath: string,
	 *   isDirect: bool
	 * }
	 */
	private function resolveStagingChunkWriteTarget(bool $createIfNotExists): array {
		$uploadStorage = $this->uploadFolder->getStorage();

		if ($createIfNotExists && !$this->uploadFolder->childExists(self::TEMP_TARGET)) {
			$this->uploadFolder->createFile(self::TEMP_TARGET);
		}

		// Use file in the upload directory that will be copied or moved afterwards
		/** @var UploadFile $uploadFile */
		$uploadFile = $this->uploadFolder->getChild(self::TEMP_TARGET);
		$file = $uploadFile->getFile();

		return [
			'file' => $file,
			'storage' => $uploadStorage,
			'storagePath' => $file->getInternalPath(),
			'isDirect' => false,
		];
	}

	/**
	 * Start a chunked upload after the upload collection is created.
	 *
	 * Resolves the upload target, starts the backend chunked-write session,
	 * and stores upload metadata in distributed cache for subsequent chunk requests.
	 */
	public function afterMkcol(RequestInterface $request, ResponseInterface $response): bool {
		try {
			$this->prepareUpload($request->getPath());
			$this->checkPrerequisites(false);
		} catch (BadRequest|StorageInvalidException|NotFound $e) {
			return true;
		}

		$headerDestination = $this->server->httpRequest->getHeader(self::DESTINATION_HEADER);
		$this->uploadPath = $this->server->calculateUri($headerDestination);

		[
			'file' => $uploadFile,
			'storage' => $storage,
			'storagePath' => $storagePath,
		] = $this->resolveChunkWriteTarget($this->uploadPath, true);

		$this->uploadId = $storage->startChunkedWrite($storagePath);

		$this->cache->set($this->uploadFolder->getName(), [
			self::UPLOAD_ID => $this->uploadId,
			self::UPLOAD_TARGET_PATH => $this->uploadPath,
			self::UPLOAD_TARGET_ID => $uploadFile->getId(),
		], 86400);

		$response->setStatus(Http::STATUS_CREATED);
		return true;
	}

	/**
	 * Store one uploaded chunk in the active chunked-write session.
	 *
	 * For staged uploads, this also updates the temporary target file metadata so
	 * size and propagation stay consistent across chunk requests.
	 *
	 * @throws BadRequest
	 * @throws InsufficientStorage
	 */
	public function beforePut(RequestInterface $request, ResponseInterface $response): bool {
		try {
			$this->prepareUpload(dirname($request->getPath()));
			$this->checkPrerequisites();
		} catch (StorageInvalidException|BadRequest|NotFound $e) {
			return true;
		}

		[
			'file' => $uploadFile,
			'storage' => $storage,
			'storagePath' => $storagePath,
			'isDirect' => $isDirect,
		] = $this->resolveChunkWriteTarget($this->uploadPath);

		$chunkName = basename($request->getPath());
		$partId = is_numeric($chunkName) ? (int)$chunkName : -1;
		if (!($partId >= 1 && $partId <= 10000)) {
			throw new BadRequest('Invalid chunk name, must be numeric between 1 and 10000');
		}

		$tempTargetFile = null;
		$additionalSize = (int)$request->getHeader('Content-Length');

		// For staged uploads, track the temporary target size separately so we can
		// reject requests that would exceed the destination's available free space.
		if (!$isDirect && $this->uploadPath) {
			/** @var UploadFile $tempTargetFile */
			$tempTargetFile = $this->uploadFolder->getChild(self::TEMP_TARGET);
			[$destinationDir, $destinationName] = Uri\split($this->uploadPath);
			/** @var Directory $destinationParent */
			$destinationParent = $this->server->tree->getNodeForPath($destinationDir);

			$free = $destinationParent->getNode()->getFreeSpace();
			$newSize = $tempTargetFile->getSize() + $additionalSize;
			if ($free >= 0 && ($tempTargetFile->getSize() > $free || $newSize > $free)) {
				throw new InsufficientStorage("Insufficient space in $this->uploadPath");
			}
		}

		$stream = $request->getBodyAsStream();
		$storage->putChunkedWritePart(
			$storagePath,
			$this->uploadId,
			(string)$partId,
			$stream,
			$additionalSize
		);

		$storage->getCache()->update(
			$uploadFile->getId(),
			['size' => $uploadFile->getSize() + $additionalSize]
		);

		if ($tempTargetFile) {
			$storage->getPropagator()->propagateChange(
				$tempTargetFile->getInternalPath(),
				time(),
				$additionalSize
			);
		}

		$response->setStatus(201);
		return false;
	}

	/**
	 * Finalize a chunked upload when the upload collection is moved to its target.
	 *
	 * Completes the backend chunked write, applies file metadata, and removes the
	 * temporary upload folder when the source is still a future file.
	 */
	public function beforeMove($sourcePath, $destination): bool {
		try {
			$this->prepareUpload(dirname($sourcePath));
			$this->checkPrerequisites();
		} catch (StorageInvalidException|BadRequest|NotFound|PreconditionFailed $e) {
			return true;
		}

		['file' => $targetFile, 'storage' => $storage] = $this->resolveChunkWriteTarget($this->uploadPath);

		[$destinationDir, $destinationName] = Uri\split($destination);
		/** @var Directory $destinationParent */
		$destinationParent = $this->server->tree->getNodeForPath($destinationDir);
		$destinationExists = $destinationParent->childExists($destinationName);

		$updateFileInfo = [];

		// allow sync clients to send the modification time along in a header
		$headerMTime = $this->server->httpRequest->getHeader('X-OC-MTime');
		if ($headerMTime !== null) {
			$updateFileInfo['mtime'] = $this->sanitizeMtime($headerMTime);
			$this->server->httpResponse->setHeader('X-OC-MTime', 'accepted');
		}

		// allow sync clients to send the creation time along in a header
		$headerCTime = $this->server->httpRequest->getHeader('X-OC-CTime');
		if ($headerCTime !== null) {
			$updateFileInfo['creation_time'] = $this->sanitizeMtime($headerCTime);
			$this->server->httpResponse->setHeader('X-OC-CTime', 'accepted');
		}

		$updateFileInfo['mimetype'] = \OCP\Server::get(IMimeTypeDetector::class)->detectPath($destinationName);

		// For multipart object-store uploads, determine the final size from uploaded
		// parts before completing the write so free-space checks reflect the real size.
		if (
			$storage->instanceOfStorage(ObjectStoreStorage::class)
			&& $storage->getObjectStore() instanceof IObjectStoreMultiPartUpload
		) {
			/** @var ObjectStoreStorage $storage */
			/** @var IObjectStoreMultiPartUpload $objectStore */
			$objectStore = $storage->getObjectStore();
			$parts = $objectStore->getMultipartUploads($storage->getURN($targetFile->getId()), $this->uploadId);
			$size = 0;
			foreach ($parts as $part) {
				$size += $part['Size'];
			}
			$free = $destinationParent->getNode()->getFreeSpace();
			if ($free >= 0 && ($size > $free)) {
				throw new InsufficientStorage("Insufficient space in $this->uploadPath");
			}
		}

		$destinationInView = $destinationParent->getFileInfo()->getPath() . '/' . $destinationName;
		$this->completeChunkedWrite($destinationInView);

		$rootView = new View();
		$rootView->putFileInfo($destinationInView, $updateFileInfo);

		$sourceNode = $this->server->tree->getNodeForPath($sourcePath);
		if ($sourceNode instanceof FutureFile) {
			$this->uploadFolder->delete();
		}

		$this->server->emit('afterMove', [$sourcePath, $destination]);
		$this->server->emit('afterUnbind', [$sourcePath]);
		$this->server->emit('afterBind', [$destination]);

		$response = $this->server->httpResponse;
		$response->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$response->setHeader('Content-Length', '0');
		$response->setStatus($destinationExists ? Http::STATUS_NO_CONTENT : Http::STATUS_CREATED);
		return false;
	}

	public function beforeDelete(RequestInterface $request, ResponseInterface $response) {
		try {
			$this->prepareUpload(dirname($request->getPath()));
			$this->checkPrerequisites();
		} catch (StorageInvalidException|BadRequest|NotFound $e) {
			return true;
		}

		['storage' => $storage, 'storagePath' => $storagePath] = $this->resolveChunkWriteTarget($this->uploadPath);
		$storage->cancelChunkedWrite($storagePath, $this->uploadId);
		
		return true;
	}

	/**
	 * Validate that the current request can use chunking v2.
	 *
	 * This checks cache availability, upload-folder capabilities, destination
	 * headers, and cached upload metadata from previous requests.
	 *
	 * @throws BadRequest
	 * @throws PreconditionFailed
	 * @throws StorageInvalidException
	 */
	private function checkPrerequisites(bool $checkUploadMetadata = true): void {
		$distributedCacheConfig = \OCP\Server::get(IConfig::class)->getSystemValue('memcache.distributed', null);

		if (
			$distributedCacheConfig === null
			|| (!$this->cache instanceof Redis && !$this->cache instanceof Memcached)
		) {
			throw new BadRequest('Skipping chunking v2 since no proper distributed cache is available');
		}

		if (
			!$this->uploadFolder instanceof UploadFolder
			|| empty($this->server->httpRequest->getHeader(self::DESTINATION_HEADER))
		) {
			throw new BadRequest('Skipping chunked file writing as the destination header was not passed');
		}

		if (!$this->uploadFolder->getStorage()->instanceOfStorage(IChunkedFileWrite::class)) {
			throw new StorageInvalidException('Storage does not support chunked file writing');
		}

		if (
			$this->uploadFolder->getStorage()->instanceOfStorage(ObjectStoreStorage::class)
			&& !$this->uploadFolder->getStorage()->getObjectStore() instanceof IObjectStoreMultiPartUpload
		) {
			throw new StorageInvalidException('Storage does not support multi part uploads');
		}

		if ($checkUploadMetadata) {
			if ($this->uploadId === null || $this->uploadPath === null) {
				throw new PreconditionFailed(
					'Missing metadata for chunked upload. The distributed cache does not hold the information of previous requests.'
				);
			}
		}
	}

	protected function sanitizeMtime(string $mtimeFromRequest): int {
		if (!is_numeric($mtimeFromRequest)) {
			throw new InvalidArgumentException('X-OC-MTime/CTime header must be an integer (unix timestamp).');
		}

		return (int)$mtimeFromRequest;
	}

	/**
	 * Load the upload folder and cached metadata for the current upload request.
	 *
	 * Populates the upload folder as well as the cached upload ID and destination
	 * path needed by subsequent chunk, move, and delete requests.
	 *
	 * FIXME: make private?
	 *
	 * @throws NotFound
	 */
	public function prepareUpload($path): void {
		$this->uploadFolder = $this->server->tree->getNodeForPath($path);
		$uploadMetadata = $this->cache->get($this->uploadFolder->getName());
		$this->uploadId = $uploadMetadata[self::UPLOAD_ID] ?? null;
		$this->uploadPath = $uploadMetadata[self::UPLOAD_TARGET_PATH] ?? null;
	}

	/**
	 * Complete the backend chunked write and materialize the final target file.
	 *
	 * When the upload was staged in the upload folder, the completed file is
	 * copied or moved to the requested destination after the backend write
	 * finishes.
	 */
	private function completeChunkedWrite(string $targetAbsolutePath): void {
		$resolvedTarget = $this->resolveChunkWriteTarget($this->uploadPath);
		$uploadFile = $resolvedTarget['file']->getNode();
		$storage = $resolvedTarget['storage'];
		$storagePath = $resolvedTarget['storagePath'];
		$isDirect = $resolvedTarget['isDirect'];

		$rootFolder = \OCP\Server::get(IRootFolder::class);
		$exists = $rootFolder->nodeExists($targetAbsolutePath);

		$uploadFile->lock(ILockingProvider::LOCK_SHARED);
		$this->emitPreHooks($targetAbsolutePath, $exists);
		try {
			$uploadFile->changeLock(ILockingProvider::LOCK_EXCLUSIVE);
			$storage->completeChunkedWrite($storagePath, $this->uploadId);
			$uploadFile->changeLock(ILockingProvider::LOCK_SHARED);
		} catch (Exception $e) {
			$uploadFile->unlock(ILockingProvider::LOCK_EXCLUSIVE);
			throw $e;
		}

		// If the file was not uploaded to the user storage directly we need to copy/move it
		try {
			if (!$isDirect) {
				$uploadFile = $rootFolder->get($uploadFile->getFileInfo()->getPath());
				if ($exists) {
					$uploadFile->copy($targetAbsolutePath);
				} else {
					$uploadFile->move($targetAbsolutePath);
				}
			}
			$this->emitPostHooks($targetAbsolutePath, $exists);
		} catch (Exception $e) {
			$uploadFile->unlock(ILockingProvider::LOCK_SHARED);
			throw $e;
		}
	}

	/**
	 * Emit filesystem hooks before the completed upload is materialized.
	 */
	private function emitPreHooks(string $target, bool $exists): void {
		$hookPath = $this->getHookPath($target);
		if (!$exists) {
			OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_create, [
				Filesystem::signal_param_path => $hookPath,
			]);
		} else {
			OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_update, [
				Filesystem::signal_param_path => $hookPath,
			]);
		}
		OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_write, [
			Filesystem::signal_param_path => $hookPath,
		]);
	}

	/**
	 * Emit filesystem hooks after the completed upload has been materialized.
	 */
	private function emitPostHooks(string $target, bool $exists): void {
		$hookPath = $this->getHookPath($target);
		if (!$exists) {
			OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_post_create, [
				Filesystem::signal_param_path => $hookPath,
			]);
		} else {
			OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_post_update, [
				Filesystem::signal_param_path => $hookPath,
			]);
		}
		OC_Hook::emit(Filesystem::CLASSNAME, Filesystem::signal_post_write, [
			Filesystem::signal_param_path => $hookPath,
		]);
	}

	private function getHookPath(string $path): ?string {
		if (!Filesystem::getView()) {
			return $path;
		}
		return Filesystem::getView()->getRelativePath($path);
	}
}
