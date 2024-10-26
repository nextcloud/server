<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

use OCP\AppFramework\Http\Response;
use OCP\Files\NotPermittedException;
use RuntimeException;

/**
 * Interface IManager
 *
 * @since 18.0.0
 */
interface IManager {
	/**
	 * Register a new editor
	 *
	 * @since 18.0.0
	 * @param IEditor $directEditor
	 */
	public function registerDirectEditor(IEditor $directEditor): void;

	/**
	 * Open the editing page for a provided token
	 *
	 * @since 18.0.0
	 * @param string $token
	 * @return Response
	 */
	public function edit(string $token): Response;

	/**
	 * Create a new token based on the file path and editor details
	 *
	 * @since 18.0.0
	 * @param string $path
	 * @param string $editorId
	 * @param string $creatorId
	 * @param null $templateId
	 * @return string
	 * @throws NotPermittedException
	 * @throws RuntimeException
	 */
	public function create(string $path, string $editorId, string $creatorId, $templateId = null): string;

	/**
	 * Get the token details for a given token
	 *
	 * @since 18.0.0
	 * @param string $token
	 * @return IToken
	 */
	public function getToken(string $token): IToken;

	/**
	 * Cleanup expired tokens
	 *
	 * @since 18.0.0
	 * @return int number of deleted tokens
	 */
	public function cleanup(): int;

	/**
	 * Check if direct editing is enabled
	 *
	 * @since 20.0.0
	 * @return bool
	 */
	public function isEnabled(): bool;

	/**
	 * @since 24.0.0
	 * @return IEditor[]
	 */
	public function getEditors(): array;
}
