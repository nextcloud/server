<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OCA\Files_Versions\Db\VersionEntity;
use OCP\Files\File;

/**
 * @since 28.0.0
 */
interface INeedSyncVersionBackend {
	/**
	 * TODO: Convert return type to strong type once all implementations are fixed.
	 * @return null|VersionEntity
	 */
	public function createVersionEntity(File $file);
	public function updateVersionEntity(File $sourceFile, int $revision, array $properties): void;
	public function deleteVersionsEntity(File $file): void;
}
