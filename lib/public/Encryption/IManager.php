<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Encryption;

use OC\Encryption\Exceptions\ModuleAlreadyExistsException;
use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OCP\AppFramework\Attribute\Consumable;

/**
 * This class provides access to files encryption apps.
 *
 * @since 8.1.0
 */
#[Consumable(since: '8.1.0')]
interface IManager {
	/**
	 * Check if encryption is available (at least one encryption module needs to be enabled)
	 *
	 * @return bool true if enabled, false if not
	 * @since 8.1.0
	 */
	public function isEnabled(): bool;

	/**
	 * Registers a callback function which must return an encryption module instance
	 *
	 * @param string $id
	 * @param string $displayName
	 * @param callable(): IEncryptionModule $callback
	 * @throws ModuleAlreadyExistsException
	 * @since 8.1.0
	 */
	public function registerEncryptionModule(string $id, string $displayName, callable $callback): void;

	/**
	 * Unregisters an encryption module
	 *
	 * @since 8.1.0
	 */
	public function unregisterEncryptionModule(string $moduleId): void;

	/**
	 * Get a list of all encryption modules.
	 *
	 * @return array<string, array{id: string, displayName: string, callback: (callable():IEncryptionModule)}>
	 * @since 8.1.0
	 */
	public function getEncryptionModules(): array;

	/**
	 * Get a specific encryption module.
	 *
	 * @param string $moduleId Empty to get the default module
	 * @throws ModuleDoesNotExistsException
	 * @since 8.1.0
	 */
	public function getEncryptionModule(string $moduleId = ''): IEncryptionModule;

	/**
	 * Get default encryption module Id
	 *
	 * @since 8.1.0
	 */
	public function getDefaultEncryptionModuleId(): string;

	/**
	 * Set default encryption module Id.
	 *
	 * @since 8.1.0
	 */
	public function setDefaultEncryptionModule(string $moduleId): bool;
}
