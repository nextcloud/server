<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OCP\Files\FileInfo;
use OCP\IUser;

/**
 * @since 15.0.0
 */
interface IVersion {
	/**
	 * @return IVersionBackend
	 * @since 15.0.0
	 */
	public function getBackend(): IVersionBackend;

	/**
	 * Get the file info of the source file
	 *
	 * @return FileInfo
	 * @since 15.0.0
	 */
	public function getSourceFile(): FileInfo;

	/**
	 * Get the id of the revision for the file
	 *
	 * @return int|string
	 * @since 15.0.0
	 */
	public function getRevisionId();

	/**
	 * Get the timestamp this version was created
	 *
	 * @return int
	 * @since 15.0.0
	 */
	public function getTimestamp(): int;

	/**
	 * Get the size of this version
	 *
	 * @return int|float
	 * @since 15.0.0
	 */
	public function getSize(): int|float;

	/**
	 * Get the name of the source file at the time of making this version
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getSourceFileName(): string;

	/**
	 * Get the mimetype of this version
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getMimeType(): string;

	/**
	 * Get the path of this version
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getVersionPath(): string;

	/**
	 * @return IUser
	 * @since 15.0.0
	 */
	public function getUser(): IUser;
}
