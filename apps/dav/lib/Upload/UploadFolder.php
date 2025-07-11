<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OCA\DAV\Connector\Sabre\Directory;
use OCP\Files\ObjectStore\IObjectStoreMultiPartUpload;
use OCP\Files\Storage\IStorage;
use OCP\ICacheFactory;
use OCP\Server;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;

class UploadFolder implements ICollection {
	public function __construct(
		private Directory $node,
		private CleanupService $cleanupService,
		private IStorage $storage,
		private string $uid,
	) {
	}

	public function createFile($name, $data = null) {
		// TODO: verify name - should be a simple number
		try {
			$this->node->createFile($name, $data);
		} catch (\Exception $e) {
			if ($this->node->childExists($name)) {
				$child = $this->node->getChild($name);
				$child->delete();
			}
			throw $e;
		}
	}

	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	public function getChild($name) {
		if ($name === '.file') {
			return new FutureFile($this->node, '.file');
		}
		return new UploadFile($this->node->getChild($name));
	}

	public function getChildren() {
		$tmpChildren = $this->node->getChildren();

		$children = [];
		$children[] = new FutureFile($this->node, '.file');

		foreach ($tmpChildren as $child) {
			$children[] = new UploadFile($child);
		}

		if ($this->storage->instanceOfStorage(ObjectStoreStorage::class)) {
			/** @var ObjectStoreStorage $storage */
			$objectStore = $this->storage->getObjectStore();
			if ($objectStore instanceof IObjectStoreMultiPartUpload) {
				$cache = Server::get(ICacheFactory::class)->createDistributed(ChunkingV2Plugin::CACHE_KEY);
				$uploadSession = $cache->get($this->getName());
				if ($uploadSession) {
					$uploadId = $uploadSession[ChunkingV2Plugin::UPLOAD_ID];
					$id = $uploadSession[ChunkingV2Plugin::UPLOAD_TARGET_ID];
					$parts = $objectStore->getMultipartUploads($this->storage->getURN($id), $uploadId);
					foreach ($parts as $part) {
						$children[] = new PartFile($this->node, $part);
					}
				}
			}
		}

		return $children;
	}

	public function childExists($name) {
		if ($name === '.file') {
			return true;
		}
		return $this->node->childExists($name);
	}

	public function delete() {
		$this->node->delete();

		// Background cleanup job is not needed anymore
		$this->cleanupService->removeJob($this->uid, $this->getName());
	}

	public function getName() {
		return $this->node->getName();
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function getLastModified() {
		return $this->node->getLastModified();
	}

	public function getStorage() {
		return $this->storage;
	}
}
