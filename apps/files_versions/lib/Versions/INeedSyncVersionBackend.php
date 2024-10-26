<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OCP\Files\File;

/**
 * @since 28.0.0
 */
interface INeedSyncVersionBackend {
	public function createVersionEntity(File $file): void;
	public function updateVersionEntity(File $sourceFile, int $revision, array $properties): void;
	public function deleteVersionsEntity(File $file): void;
}
