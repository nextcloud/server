<?php

/**
 * ownCloud - manage encryption modules
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCP\Encryption;

use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\Encryption\Exceptions\ModuleAlreadyExistsException;

/**
 * This class provides access to files encryption apps.
 *
 */
interface IManager {

	/**
	 * Check if encryption is available (at least one encryption module needs to be enabled)
	 *
	 * @return bool true if enabled, false if not
	 */
	function isEnabled();

	/**
	 * Registers an encryption module
	 *
	 * @param IEncryptionModule $module
	 * @throws ModuleAlreadyExistsException
	 */
	function registerEncryptionModule(IEncryptionModule $module);

	/**
	 * Unregisters an encryption module
	 *
	 * @param IEncryptionModule $module
	 */
	function unregisterEncryptionModule(IEncryptionModule $module);

	/**
	 * get a list of all encryption modules
	 *
	 * @return array
	 */
	function getEncryptionModules();


	/**
	 * get a specific encryption module
	 *
	 * @param string $moduleId
	 * @return IEncryptionModule
	 * @throws ModuleDoesNotExistsException
	 */
	function getEncryptionModule($moduleId);

	/**
	 * get default encryption module
	 *
	 * @return \OCP\Encryption\IEncryptionModule
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	public function getDefaultEncryptionModule();

	/**
	 * set default encryption module Id
	 *
	 * @param string $moduleId
	 * @return string
	 */
	public function setDefaultEncryptionModule($moduleId);

}
