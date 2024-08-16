<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Files\FileInfo;
use OCP\IUser;

abstract class AbstractTrash implements ITrash {
	/** @var ITrashItem */
	protected $data;

	/** @var ITrashManager */
	protected $trashManager;

	public function __construct(ITrashManager $trashManager, ITrashItem $data) {
		$this->trashManager = $trashManager;
		$this->data = $data;
	}

	public function getFilename(): string {
		return $this->data->getName();
	}

	public function getDeletionTime(): int {
		return $this->data->getDeletedTime();
	}

	public function getFileId(): int {
		return $this->data->getId();
	}

	public function getFileInfo(): FileInfo {
		return $this->data;
	}

	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 * @return int|float
	 */
	public function getSize(): int|float {
		return $this->data->getSize();
	}

	public function getLastModified(): int {
		return $this->data->getMtime();
	}

	public function getContentType(): string {
		return $this->data->getMimetype();
	}

	public function getETag(): string {
		return $this->data->getEtag();
	}

	public function getName(): string {
		return $this->data->getName();
	}

	public function getOriginalLocation(): string {
		return $this->data->getOriginalLocation();
	}

	public function getTitle(): string {
		return $this->data->getTitle();
	}

	public function getDeletedBy(): ?IUser {
		return $this->data->getDeletedBy();
	}

	public function delete() {
		$this->trashManager->removeItem($this->data);
	}

	public function restore(): bool {
		$this->trashManager->restoreItem($this->data);
		return true;
	}
}
