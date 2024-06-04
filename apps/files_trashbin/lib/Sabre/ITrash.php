<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCP\Files\FileInfo;
use OCP\IUser;

interface ITrash {
	public function restore(): bool;

	public function getFilename(): string;

	public function getOriginalLocation(): string;

	public function getTitle(): string;

	public function getDeletionTime(): int;

	public function getDeletedBy(): ?IUser;

	public function getSize(): int|float;

	public function getFileId(): int;

	public function getFileInfo(): FileInfo;
}
