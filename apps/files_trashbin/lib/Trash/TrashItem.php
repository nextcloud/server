<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
use OCP\IUser;

class TrashItem implements ITrashItem {

	public function __construct(
		private ITrashBackend $backend,
		private string $originalLocation,
		private int $deletedTime,
		private string $trashPath,
		private FileInfo $fileInfo,
		private IUser $user,
		private ?IUser $deletedBy,
	) {
	}

	#[\Override]
	public function getTrashBackend(): ITrashBackend {
		return $this->backend;
	}

	#[\Override]
	public function getOriginalLocation(): string {
		return $this->originalLocation;
	}

	#[\Override]
	public function getDeletedTime(): int {
		return $this->deletedTime;
	}

	#[\Override]
	public function getTrashPath(): string {
		return $this->trashPath;
	}

	#[\Override]
	public function isRootItem(): bool {
		return substr_count($this->getTrashPath(), '/') === 1;
	}

	#[\Override]
	public function getUser(): IUser {
		return $this->user;
	}

	#[\Override]
	public function getEtag() {
		return $this->fileInfo->getEtag();
	}

	#[\Override]
	public function getSize($includeMounts = true) {
		return $this->fileInfo->getSize($includeMounts);
	}

	#[\Override]
	public function getMtime() {
		return $this->fileInfo->getMtime();
	}

	#[\Override]
	public function getName() {
		return $this->fileInfo->getName();
	}

	#[\Override]
	public function getInternalPath() {
		return $this->fileInfo->getInternalPath();
	}

	#[\Override]
	public function getPath() {
		return $this->fileInfo->getPath();
	}

	#[\Override]
	public function getMimetype(): string {
		return $this->fileInfo->getMimetype();
	}

	#[\Override]
	public function getMimePart() {
		return $this->fileInfo->getMimePart();
	}

	#[\Override]
	public function getStorage() {
		return $this->fileInfo->getStorage();
	}

	#[\Override]
	public function getId() {
		return $this->fileInfo->getId();
	}

	#[\Override]
	public function isEncrypted() {
		return $this->fileInfo->isEncrypted();
	}

	#[\Override]
	public function getPermissions() {
		return $this->fileInfo->getPermissions();
	}

	#[\Override]
	public function getType() {
		return $this->fileInfo->getType();
	}

	#[\Override]
	public function isReadable() {
		return $this->fileInfo->isReadable();
	}

	#[\Override]
	public function isUpdateable() {
		return $this->fileInfo->isUpdateable();
	}

	#[\Override]
	public function isCreatable() {
		return $this->fileInfo->isCreatable();
	}

	#[\Override]
	public function isDeletable() {
		return $this->fileInfo->isDeletable();
	}

	#[\Override]
	public function isShareable() {
		return $this->fileInfo->isShareable();
	}

	#[\Override]
	public function isShared() {
		return $this->fileInfo->isShared();
	}

	#[\Override]
	public function isMounted() {
		return $this->fileInfo->isMounted();
	}

	#[\Override]
	public function getMountPoint() {
		return $this->fileInfo->getMountPoint();
	}

	#[\Override]
	public function getOwner() {
		return $this->fileInfo->getOwner();
	}

	#[\Override]
	public function getChecksum() {
		return $this->fileInfo->getChecksum();
	}

	#[\Override]
	public function getExtension(): string {
		return $this->fileInfo->getExtension();
	}

	#[\Override]
	public function getTitle(): string {
		return $this->getOriginalLocation();
	}

	#[\Override]
	public function getCreationTime(): int {
		return $this->fileInfo->getCreationTime();
	}

	#[\Override]
	public function getUploadTime(): int {
		return $this->fileInfo->getUploadTime();
	}

	#[\Override]
	public function getLastActivity(): int {
		return $this->fileInfo->getLastActivity();
	}

	#[\Override]
	public function getParentId(): int {
		return $this->fileInfo->getParentId();
	}

	#[\Override]
	public function getDeletedBy(): ?IUser {
		return $this->deletedBy;
	}

	/**
	 * @inheritDoc
	 * @return array<string, int|string|bool|float|string[]|int[]>
	 */
	#[\Override]
	public function getMetadata(): array {
		return $this->fileInfo->getMetadata();
	}

	#[\Override]
	public function getData(): ICacheEntry {
		return $this->fileInfo->getData();
	}
}
