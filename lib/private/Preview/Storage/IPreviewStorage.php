<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use Exception;
use OC\Files\SimpleFS\SimpleFile;
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
	 * Migration helper
	 *
	 * To remove at some point
	 * @throws Exception
	 */
	public function migratePreview(Preview $preview, SimpleFile $file): void;

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function scan(): int;
}
