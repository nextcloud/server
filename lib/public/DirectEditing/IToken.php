<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

use OCP\Files\File;
use OCP\Files\NotFoundException;

/**
 * @since 18.0.0
 */
interface IToken {
	/**
	 * Extend the token validity time
	 *
	 * @since 18.0.0
	 */
	public function extend(): void;

	/**
	 * Invalidate the token
	 *
	 * @since 18.0.0
	 */
	public function invalidate(): void;

	/**
	 * Check if the token has already been used
	 *
	 * @since 18.0.0
	 * @return bool
	 */
	public function hasBeenAccessed(): bool;

	/**
	 * Change to the user scope of the token
	 *
	 * @since 18.0.0
	 */
	public function useTokenScope(): void;

	/**
	 * Get the file that is related to the token
	 *
	 * @since 18.0.0
	 * @return File
	 * @throws NotFoundException
	 */
	public function getFile(): File;

	/**
	 * @since 18.0.0
	 * @return string
	 */
	public function getEditor(): string;

	/**
	 * @since 18.0.0
	 * @return string
	 */
	public function getUser(): string;
}
