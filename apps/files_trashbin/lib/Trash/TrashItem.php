<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

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

	public function getTrashBackend(): ITrashBackend {
		return $this->backend;
	}

	public function getOriginalLocation(): string {
		return $this->originalLocation;
	}

	public function getDeletedTime(): int {
		return $this->deletedTime;
	}

	public function getTrashPath(): string {
		return $this->trashPath;
	}

	public function isRootItem(): bool {
		return substr_count($this->getTrashPath(), '/') === 1;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function getEtag() {
		return $this->fileInfo->getEtag();
	}

	public function getSize($includeMounts = true) {
		return $this->fileInfo->getSize($includeMounts);
	}

	public function getMtime() {
		return $this->fileInfo->getMtime();
	}

	public function getName() {
		return $this->fileInfo->getName();
	}

	public function getInternalPath() {
		return $this->fileInfo->getInternalPath();
	}

	public function getPath() {
		return $this->fileInfo->getPath();
	}

	public function getMimetype(): string {
		return $this->fileInfo->getMimetype();
	}

	public function getMimePart() {
		return $this->fileInfo->getMimePart();
	}

	public function getStorage() {
		return $this->fileInfo->getStorage();
	}

	public function getId() {
		return $this->fileInfo->getId();
	}

	public function isEncrypted() {
		return $this->fileInfo->isEncrypted();
	}

	public function getPermissions() {
		return $this->fileInfo->getPermissions();
	}

	public function getType() {
		return $this->fileInfo->getType();
	}

	public function isReadable() {
		return $this->fileInfo->isReadable();
	}

	public function isUpdateable() {
		return $this->fileInfo->isUpdateable();
	}

	public function isCreatable() {
		return $this->fileInfo->isCreatable();
	}

	public function isDeletable() {
		return $this->fileInfo->isDeletable();
	}

	public function isShareable() {
		return $this->fileInfo->isShareable();
	}

	public function isShared() {
		return $this->fileInfo->isShared();
	}

	public function isMounted() {
		return $this->fileInfo->isMounted();
	}

	public function getMountPoint() {
		return $this->fileInfo->getMountPoint();
	}

	public function getOwner() {
		return $this->fileInfo->getOwner();
	}

	public function getChecksum() {
		return $this->fileInfo->getChecksum();
	}

	public function getExtension(): string {
		return $this->fileInfo->getExtension();
	}

	public function getTitle(): string {
		return $this->getOriginalLocation();
	}

	public function getCreationTime(): int {
		return $this->fileInfo->getCreationTime();
	}

	public function getUploadTime(): int {
		return $this->fileInfo->getUploadTime();
	}

	public function getParentId(): int {
		return $this->fileInfo->getParentId();
	}

	public function getDeletedBy(): ?IUser {
		return $this->deletedBy;
	}

	/**
	 * @inheritDoc
	 * @return array<string, int|string|bool|float|string[]|int[]>
	 */
	public function getMetadata(): array {
		return $this->fileInfo->getMetadata();
	}
}
