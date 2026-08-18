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
	 * Delete the stored data of a preview that never got a database row, for
	 * example after losing the insert race on the unique constraint.
	 *
	 * Only backends that key their storage by the preview id hold data that
	 * belongs to this entity alone. The local storage derives its path from
	 * the preview specification, so the file is the very same one the winner
	 * of the race is now referencing and has to be kept.
	 *
	 * @throws NotPermittedException
	 */
	public function deleteUnreferencedPreview(Preview $preview): void;

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
