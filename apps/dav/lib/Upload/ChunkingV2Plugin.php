<?php

declare(strict_types=1);
/*
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

class ChunkingV2Plugin extends ServerPlugin {
	/** @var Server */
	private $server;
	/** @var UploadFolder */
	private $uploadFolder;
	/** @var ICache */
	private $cache;

	private ?string $uploadId = null;
	private ?string $uploadPath = null;

	private const TEMP_TARGET = '.target';

	public const CACHE_KEY = 'chunking-v2';
	public const UPLOAD_TARGET_PATH = 'upload-target-path';
	public const UPLOAD_TARGET_ID = 'upload-target-id';
	public const UPLOAD_ID = 'upload-id';

	private const DESTINATION_HEADER = 'Destination';

	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->createDistributed(self::CACHE_KEY);
	}

	/**
	 * @inheritdoc
	 */
	public function initialize(Server $server) {
		$server->on('afterMethod:MKCOL', [$this, 'afterMkcol']);
		$server->on('beforeMethod:PUT', [$this, 'beforePut']);
		$server->on('beforeMethod:DELETE', [$this, 'beforeDelete']);
		$server->on('beforeMove', [$this, 'beforeMove'], 90);

		$this->server = $server;
	}

	/**
	 * @param string $path
	 * @param bool $createIfNotExists
	 * @return FutureFile|UploadFile|ICollection|INode
	 */
	private function getUploadFile(string $path, bool $createIfNotExists = false) {
		try {
			$actualFile = $this->server->tree->getNodeForPath($path);
			// Only directly upload to the target file if it is on the same storage
			// There may be further potential to optimize here by also uploading
			// to other storages directly. This would require to also carefully pick
			// the storage/path used in getStorage()
			if ($actualFile instanceof File && $this->uploadFolder->getStorage()->getId() === $actualFile->getNode()->getStorage()->getId()) {
				return $actualFile;
			}
		} catch (NotFound $e) {
			// If there is no target file we upload to the upload folder first
		}

		// Use file in the upload directory that will be copied or moved afterwards
		if ($createIfNotExists) {
			$this->uploadFolder->createFile(self::TEMP_TARGET);
		}

		/** @var UploadFile $uploadFile */
		$uploadFile = $this->uploadFolder->getChild(self::TEMP_TARGET);
		return $uploadFile->getFile();
	}

	public function afterMkcol(RequestInterface $request, ResponseInterface $response): bool {
		try {
			$this->prepareUpload($request->getPath());
			$this->checkPrerequisites(false);
		} catch (BadRequest|StorageInvalidException|NotFound $e) {
			return true;
		}

		$this->uploadPath = $this->server->calculateUri($this->server->httpRequest->getHeader(self::DESTINATION_HEADER));
		$targetFile = $this->getUploadFile($this->uploadPath, true);
		[$storage, $storagePath] = $this->getUploadStorage($this->uploadPath);

		$this->uploadId = $storage->startChunkedWrite($storagePath);

		$this->cache->set($this->uploadFolder->getName(), [
			self::UPLOAD_ID => $this->uploadId,
			self::UPLOAD_TARGET_PATH => $this->uploadPath,
			self::UPLOAD_TARGET_ID => $targetFile->getId(),
		], 86400);

		$response->setStatus(201);
		return true;
	}

	public function beforePut(RequestInterface $request, ResponseInterface $response): bool {
		try {
			$this->prepareUpload(dirname($request->getPath()));
			$this->checkPrerequisites();
		} catch (StorageInvalidException|BadRequest|NotFound $e) {
			return true;
		}

		[$storage, $storagePath] = $this->getUploadStorage($this->uploadPath);

		$chunkName = basename($request->getPath());
		$partId = is_numeric($chunkName) ? (int)$chunkName : -1;
		if (!($partId >= 1 && $partId <= 10000)) {
			throw new BadRequest('Invalid chunk name, must be numeric between 1 and 10000');
		}

		$uploadFile = $this->getUploadFile($this->uploadPath);
		$tempTargetFile = null;

		$additionalSize = (int)$request->getHeader('Content-Length');
		if ($this->uploadFolder->childExists(self::TEMP_TARGET) && $this->uploadPath) {
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
		$storage->putChunkedWritePart($storagePath, $this->uploadId, (string)$partId, $stream, $additionalSize);

		$storage->getCache()->update($uploadFile->getId(), ['size' => $uploadFile->getSize() + $additionalSize]);
		if ($tempTargetFile) {
			$storage->getPropagator()->propagateChange($tempTargetFile->getInternalPath(), time(), $additionalSize);
		}

		$response->setStatus(201);
		return false;
	}

	public function beforeMove($sourcePath, $destination): bool {
		try {
			$this->prepareUpload(dirname($sourcePath));
			$this->checkPrerequisites();
		} catch (StorageInvalidException|BadRequest|NotFound|PreconditionFailed $e) {
			return true;
		}
		[$storage, $storagePath] = $this->getUploadStorage($this->uploadPath);

		$targetFile = $this->getUploadFile($this->uploadPath);

		[$destinationDir, $destinationName] = Uri\split($destination);
		/** @var Directory $destinationParent */
		$destinationParent = $this->server->tree->getNodeForPath($destinationDir);
		$destinationExists = $destinationParent->childExists($destinationName);


		// allow sync clients to send the modification and creation time along in a header
		$updateFileInfo = [];
		if ($this->server->httpRequest->getHeader('X-OC-MTime') !== null) {
			$updateFileInfo['mtime'] = $this->sanitizeMtime($this->server->httpRequest->getHeader('X-OC-MTime'));
			$this->server->httpResponse->setHeader('X-OC-MTime', 'accepted');
		}
		if ($this->server->httpRequest->getHeader('X-OC-CTime') !== null) {
			$updateFileInfo['creation_time'] = $this->sanitizeMtime($this->server->httpRequest->getHeader('X-OC-CTime'));
			$this->server->httpResponse->setHeader('X-OC-CTime', 'accepted');
		}
		$updateFileInfo['mimetype'] = \OCP\Server::get(IMimeTypeDetector::class)->detectPath($destinationName);

		if ($storage->instanceOfStorage(ObjectStoreStorage::class) && $storage->getObjectStore() instanceof IObjectStoreMultiPartUpload) {
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
		$response->setStatus($destinationExists ? 204 : 201);
		return false;
	}

	public function beforeDelete(RequestInterface $request, ResponseInterface $response) {
		try {
			$this->prepareUpload(dirname($request->getPath()));
			$this->checkPrerequisites();
		} catch (StorageInvalidException|BadRequest|NotFound $e) {
			return true;
		}

		[$storage, $storagePath] = $this->getUploadStorage($this->uploadPath);
		$storage->cancelChunkedWrite($storagePath, $this->uploadId);
		return true;
	}

	/**
	 * @throws BadRequest
	 * @throws PreconditionFailed
	 * @throws StorageInvalidException
	 */
	private function checkPrerequisites(bool $checkUploadMetadata = true): void {
		$distributedCacheConfig = \OCP\Server::get(IConfig::class)->getSystemValue('memcache.distributed', null);

		if ($distributedCacheConfig === null || (!$this->cache instanceof Redis && !$this->cache instanceof Memcached)) {
			throw new BadRequest('Skipping chunking v2 since no proper distributed cache is available');
		}
		if (!$this->uploadFolder instanceof UploadFolder || empty($this->server->httpRequest->getHeader(self::DESTINATION_HEADER))) {
			throw new BadRequest('Skipping chunked file writing as the destination header was not passed');
		}
		if (!$this->uploadFolder->getStorage()->instanceOfStorage(IChunkedFileWrite::class)) {
			throw new StorageInvalidException('Storage does not support chunked file writing');
		}
		if ($this->uploadFolder->getStorage()->instanceOfStorage(ObjectStoreStorage::class) && !$this->uploadFolder->getStorage()->getObjectStore() instanceof IObjectStoreMultiPartUpload) {
			throw new StorageInvalidException('Storage does not support multi part uploads');
		}

		if ($checkUploadMetadata) {
			if ($this->uploadId === null || $this->uploadPath === null) {
				throw new PreconditionFailed('Missing metadata for chunked upload. The distributed cache does not hold the information of previous requests.');
			}
		}
	}

	/**
	 * @return array [IStorage, string]
	 */
	private function getUploadStorage(string $targetPath): array {
		$storage = $this->uploadFolder->getStorage();
		$targetFile = $this->getUploadFile($targetPath);
		return [$storage, $targetFile->getInternalPath()];
	}

	protected function sanitizeMtime(string $mtimeFromRequest): int {
		if (!is_numeric($mtimeFromRequest)) {
			throw new InvalidArgumentException('X-OC-MTime header must be an integer (unix timestamp).');
		}

		return (int)$mtimeFromRequest;
	}

	/**
	 * @throws NotFound
	 */
	public function prepareUpload($path): void {
		$this->uploadFolder = $this->server->tree->getNodeForPath($path);
		$uploadMetadata = $this->cache->get($this->uploadFolder->getName());
		$this->uploadId = $uploadMetadata[self::UPLOAD_ID] ?? null;
		$this->uploadPath = $uploadMetadata[self::UPLOAD_TARGET_PATH] ?? null;
	}

	private function completeChunkedWrite(string $targetAbsolutePath): void {
		$uploadFile = $this->getUploadFile($this->uploadPath)->getNode();
		[$storage, $storagePath] = $this->getUploadStorage($this->uploadPath);

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
			$uploadFileAbsolutePath = $uploadFile->getFileInfo()->getPath();
			if ($uploadFileAbsolutePath !== $targetAbsolutePath) {
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
