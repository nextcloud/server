<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use Exception;
use OC\Preview\Db\Preview;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

interface IPreviewStorage {
	/**
	 * @param resource $stream
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function writePreview(Preview $preview, mixed $stream): int;

	/**
	 * @param Preview $preview
	 * @return resource
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function readPreview(Preview $preview): mixed;

	/**
	 * @throws NotPermittedException
	 */
	public function deletePreview(Preview $preview): void;

	/**
	 * Whether the stored data of a preview is still present.
	 *
	 * A row can outlive its file, for instance when the data directory is
	 * restored from an older backup, and every consumer of a preview assumes
	 * it can be read.
	 */
	public function previewExists(Preview $preview): bool;

	/**
	 * Migration helper
	 *
	 * To remove at some point
	 * @throws Exception
	 */
	public function migratePreview(Preview $preview): void;

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function scan(): int;
}
