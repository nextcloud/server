<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

use OCP\Files\FileInfo;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use Override;

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

	public function getDeletedBy(): ?IUser {
		return $this->deletedBy;
	}

	#[Override]
	public function getEtag(): string {
		return $this->fileInfo->getEtag();
	}

	#[Override]
	public function getSize(bool $includeMounts = true): int|float {
		return $this->fileInfo->getSize($includeMounts);
	}

	#[Override]
	public function getMtime(): int {
		return $this->fileInfo->getMtime();
	}

	#[Override]
	public function getName(): string {
		return $this->fileInfo->getName();
	}

	#[Override]
	public function getInternalPath(): string {
		return $this->fileInfo->getInternalPath();
	}

	#[Override]
	public function getPath(): string {
		return $this->fileInfo->getPath();
	}

	#[Override]
	public function getMimetype(): string {
		return $this->fileInfo->getMimetype();
	}

	#[Override]
	public function getMimePart(): string {
		return $this->fileInfo->getMimePart();
	}

	#[Override]
	public function getStorage(): IStorage {
		return $this->fileInfo->getStorage();
	}

	#[Override]
	public function getId(): int {
		return $this->fileInfo->getId();
	}

	#[Override]
	public function isEncrypted(): bool {
		return $this->fileInfo->isEncrypted();
	}

	#[Override]
	public function getPermissions(): int {
		return $this->fileInfo->getPermissions();
	}

	#[Override]
	public function getType(): string {
		return $this->fileInfo->getType();
	}

	#[Override]
	public function isReadable(): bool {
		return $this->fileInfo->isReadable();
	}

	#[Override]
	public function isUpdateable(): bool {
		return $this->fileInfo->isUpdateable();
	}

	#[Override]
	public function isCreatable(): bool {
		return $this->fileInfo->isCreatable();
	}

	#[Override]
	public function isDeletable(): bool {
		return $this->fileInfo->isDeletable();
	}

	public function isShareable(): bool {
		return $this->fileInfo->isShareable();
	}

	#[Override]
	public function isShared(): bool {
		return $this->fileInfo->isShared();
	}

	#[Override]
	public function isMounted(): bool {
		return $this->fileInfo->isMounted();
	}

	#[Override]
	public function getMountPoint(): IMountPoint {
		return $this->fileInfo->getMountPoint();
	}

	#[Override]
	public function getOwner(): ?IUser {
		return $this->fileInfo->getOwner();
	}

	#[Override]
	public function getChecksum(): string {
		return $this->fileInfo->getChecksum();
	}

	#[Override]
	public function getExtension(): string {
		return $this->fileInfo->getExtension();
	}

	#[Override]
	public function getTitle(): string {
		return $this->getOriginalLocation();
	}

	#[Override]
	public function getCreationTime(): int {
		return $this->fileInfo->getCreationTime();
	}

	#[Override]
	public function getUploadTime(): int {
		return $this->fileInfo->getUploadTime();
	}

	#[Override]
	public function getParentId(): int {
		return $this->fileInfo->getParentId();
	}

	#[Override]
	public function getMetadata(): array {
		return $this->fileInfo->getMetadata();
	}
}
