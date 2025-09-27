<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OCP\Files\NotPermittedException;

interface IPreviewStorage {
	/**
	 * @param resource|string $stream
	 * @throws NotPermittedException
	 */
	public function writePreview(Preview $preview, mixed $stream): false|int;

	/**
	 * @param Preview $preview
	 * @return resource|false
	 */
	public function readPreview(Preview $preview): mixed;

	public function deletePreview(Preview $preview): void;

	/**
	 * Migration helper
	 *
	 * To remove at some point
	 * @throw \Exception
	 */
	public function migratePreview(Preview $preview, SimpleFile $file): void;

	public function scan(): int;
}
