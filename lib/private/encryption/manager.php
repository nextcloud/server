<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Encryption;

use OC\Files\Storage\Shared;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\Files\Mount\IMountPoint;
use OCP\IConfig;
use OCP\ILogger;

class Manager implements IManager {

	/** @var array */
	protected $encryptionModules;

	/** @var IConfig */
	protected $config;

	/** @var ILogger */
	protected $logger;

	/**
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, ILogger $logger) {
		$this->encryptionModules = array();
		$this->config = $config;
		$this->logger = $logger;
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
	 * check if new encryption is ready
	 *
	 * @return boolean
	 */
	public function isReady() {
		// check if we are still in transit between the old and the new encryption
		$oldEncryption = $this->config->getAppValue('files_encryption', 'installed_version');
		if (!empty($oldEncryption)) {
			$warning = 'Installation is in transit between the old Encryption (ownCloud <= 8.0)
			and the new encryption. Please enable the "ownCloud Default Encryption Module"
			and run \'occ encryption:migrate\'';
			$this->logger->warning($warning);
			return false;
		}
		return true;
	}

	/**
	 * Registers an callback function which must return an encryption module instance
	 *
	 * @param string $id
	 * @param string $displayName
	 * @param callable $callback
	 * @throws Exceptions\ModuleAlreadyExistsException
	 */
	public function registerEncryptionModule($id, $displayName, callable $callback) {

		if (isset($this->encryptionModules[$id])) {
			throw new Exceptions\ModuleAlreadyExistsException($id, $displayName);
		}

		$defaultEncryptionModuleId = $this->getDefaultEncryptionModuleId();

		if (empty($defaultEncryptionModuleId)) {
			$this->setDefaultEncryptionModule($id);
		}

		$this->encryptionModules[$id] = [
			'id' => $id,
			'displayName' => $displayName,
			'callback' => $callback,
		];
	}

	/**
	 * Unregisters an encryption module
	 *
	 * @param string $moduleId
	 */
	public function unregisterEncryptionModule($moduleId) {
		unset($this->encryptionModules[$moduleId]);
	}

	/**
	 * get a list of all encryption modules
	 *
	 * @return array [id => ['id' => $id, 'displayName' => $displayName, 'callback' => callback]]
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
				return call_user_func($this->encryptionModules[$moduleId]['callback']);
			} else {
				$message = "Module with id: $moduleId does not exists.";
				throw new Exceptions\ModuleDoesNotExistsException($message);
			}
		} else {
			return $this->getDefaultEncryptionModule();
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
				return call_user_func($this->encryptionModules[$defaultModuleId]['callback']);
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

			if (!($storage instanceof Shared)) {
				$manager = \OC::$server->getEncryptionManager();
				$util = new Util(
					new View(),
					\OC::$server->getUserManager(),
					\OC::$server->getGroupManager(),
					\OC::$server->getConfig()
				);
				$user = \OC::$server->getUserSession()->getUser();
				$logger = \OC::$server->getLogger();
				$uid = $user ? $user->getUID() : null;
				$fileHelper = \OC::$server->getEncryptionFilesHelper();
				$keyStorage = \OC::$server->getEncryptionKeyStorage();
				return new Encryption($parameters, $manager, $util, $logger, $fileHelper, $uid, $keyStorage);
			} else {
				return $storage;
			}
		}, 2);
	}
}
