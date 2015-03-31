<?php

/**
 * ownCloud
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

namespace OC\Encryption;

use OC\Files\Storage\Wrapper\Encryption;
use OCP\Encryption\IEncryptionModule;
use OCP\Files\Mount\IMountPoint;

class Manager implements \OCP\Encryption\IManager {

	/** @var array */
	protected $encryptionModules;

	/** @var \OCP\IConfig */
	protected $config;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(\OCP\IConfig $config) {
		$this->encryptionModules = array();
		$this->config = $config;
	}

	/**
	 * Check if encryption is enabled
	 *
	 * @return bool true if enabled, false if not
	 */
	public function isEnabled() {

		$installed = $this->config->getSystemValue('installed', false);
		if (!$installed) {
			return false;
		}

		$enabled = $this->config->getAppValue('core', 'encryption_enabled', 'no');
		return $enabled === 'yes';
	}

	/**
	 * Registers an encryption module
	 *
	 * @param IEncryptionModule $module
	 * @throws Exceptions\ModuleAlreadyExistsException
	 */
	public function registerEncryptionModule(IEncryptionModule $module) {
		$id = $module->getId();
		$name = $module->getDisplayName();

		if (isset($this->encryptionModules[$id])) {
			$message = 'Id "' . $id . '" already used by encryption module "' . $name . '"';
			throw new Exceptions\ModuleAlreadyExistsException($message);
		}

		$defaultEncryptionModuleId = $this->getDefaultEncryptionModuleId();

		if (empty($defaultEncryptionModuleId)) {
			$this->setDefaultEncryptionModule($id);
		}

		$this->encryptionModules[$id] = $module;
	}

	/**
	 * Unregisters an encryption module
	 *
	 * @param IEncryptionModule $module
	 */
	public function unregisterEncryptionModule(IEncryptionModule $module) {
		unset($this->encryptionModules[$module->getId()]);
	}

	/**
	 * get a list of all encryption modules
	 *
	 * @return IEncryptionModule[]
	 */
	public function getEncryptionModules() {
		return $this->encryptionModules;
	}

	/**
	 * get a specific encryption module
	 *
	 * @param string $moduleId
	 * @return IEncryptionModule
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	public function getEncryptionModule($moduleId = '') {
		if (!empty($moduleId)) {
			if (isset($this->encryptionModules[$moduleId])) {
				return $this->encryptionModules[$moduleId];
			} else {
				$message = "Module with id: $moduleId does not exists.";
				throw new Exceptions\ModuleDoesNotExistsException($message);
			}
		} else { // get default module and return this
				 // For now we simply return the first module until we have a way
	             // to enable multiple modules and define a default module
			$module = reset($this->encryptionModules);
			if ($module) {
				return $module;
			} else {
				$message = 'No encryption module registered';
				throw new Exceptions\ModuleDoesNotExistsException($message);
			}
		}
	}

	/**
	 * get default encryption module
	 *
	 * @return \OCP\Encryption\IEncryptionModule
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	public function getDefaultEncryptionModule() {
		$defaultModuleId = $this->getDefaultEncryptionModuleId();
		if (!empty($defaultModuleId)) {
			if (isset($this->encryptionModules[$defaultModuleId])) {
				return $this->encryptionModules[$defaultModuleId];
			} else {
				$message = 'Default encryption module not loaded';
				throw new Exceptions\ModuleDoesNotExistsException($message);
			}
		} else {
			$message = 'No default encryption module defined';
			throw new Exceptions\ModuleDoesNotExistsException($message);
		}

	}

	/**
	 * set default encryption module Id
	 *
	 * @param string $moduleId
	 * @return bool
	 */
	public function setDefaultEncryptionModule($moduleId) {
		try {
			$this->config->setAppValue('core', 'default_encryption_module', $moduleId);
			return true;
		} catch (\Exception $e) {
			return false;
		}

	}

	/**
	 * get default encryption module Id
	 *
	 * @return string
	 */
	protected function getDefaultEncryptionModuleId() {
		try {
			return $this->config->getAppValue('core', 'default_encryption_module');
		} catch (\Exception $e) {
			return '';
		}
	}

	public static function setupStorage() {
		\OC\Files\Filesystem::addStorageWrapper('oc_encryption', function ($mountPoint, $storage, IMountPoint $mount) {
			$parameters = [
				'storage' => $storage,
				'mountPoint' => $mountPoint,
				'mount' => $mount];
			$manager = \OC::$server->getEncryptionManager();
			$util = new \OC\Encryption\Util(
				new \OC\Files\View(),
				\OC::$server->getUserManager(),
				\OC::$server->getConfig());
			$user = \OC::$server->getUserSession()->getUser();
			$logger = \OC::$server->getLogger();
			$uid = $user ? $user->getUID() : null;
			return new Encryption($parameters, $manager, $util, $logger, $uid);
		});
	}
}
