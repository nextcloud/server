<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Encryption;

use OC\Encryption\Exceptions\ModuleAlreadyExistsException;
use OC\Encryption\Exceptions\ModuleDoesNotExistsException;

/**
 * This class provides access to files encryption apps.
 *
 * @since 8.1.0
 */
interface IManager {
	/**
	 * Check if encryption is available (at least one encryption module needs to be enabled)
	 *
	 * @return bool true if enabled, false if not
	 * @since 8.1.0
	 */
	public function isEnabled();

	/**
	 * Registers an callback function which must return an encryption module instance
	 *
	 * @param string $id
	 * @param string $displayName
	 * @param callable $callback
	 * @throws ModuleAlreadyExistsException
	 * @since 8.1.0
	 */
	public function registerEncryptionModule($id, $displayName, callable $callback);

	/**
	 * Unregisters an encryption module
	 *
	 * @param string $moduleId
	 * @since 8.1.0
	 */
	public function unregisterEncryptionModule($moduleId);

	/**
	 * get a list of all encryption modules
	 *
	 * @return array [id => ['id' => $id, 'displayName' => $displayName, 'callback' => callback]]
	 * @since 8.1.0
	 */
	public function getEncryptionModules();


	/**
	 * get a specific encryption module
	 *
	 * @param string $moduleId Empty to get the default module
	 * @return IEncryptionModule
	 * @throws ModuleDoesNotExistsException
	 * @since 8.1.0
	 */
	public function getEncryptionModule($moduleId = '');

	/**
	 * get default encryption module Id
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getDefaultEncryptionModuleId();

	/**
	 * set default encryption module Id
	 *
	 * @param string $moduleId
	 * @return string
	 * @since 8.1.0
	 */
	public function setDefaultEncryptionModule($moduleId);
}
