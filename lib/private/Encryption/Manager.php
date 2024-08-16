<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption;

use OC\Encryption\Keys\Storage;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OC\ServiceUnavailableException;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	protected array $encryptionModules;

	public function __construct(
		protected IConfig $config,
		protected LoggerInterface $logger,
		protected IL10N $l,
		protected View $rootView,
		protected Util $util,
		protected ArrayCache $arrayCache,
	) {
		$this->encryptionModules = [];
	}

	/**
	 * Check if encryption is enabled
	 *
	 * @return bool true if enabled, false if not
	 */
	public function isEnabled() {
		$installed = $this->config->getSystemValueBool('installed', false);
		if (!$installed) {
			return false;
		}

		$enabled = $this->config->getAppValue('core', 'encryption_enabled', 'no');
		return $enabled === 'yes';
	}

	/**
	 * check if new encryption is ready
	 *
	 * @return bool
	 * @throws ServiceUnavailableException
	 */
	public function isReady() {
		if ($this->isKeyStorageReady() === false) {
			throw new ServiceUnavailableException('Key Storage is not ready');
		}

		return true;
	}

	/**
	 * @param string $user
	 */
	public function isReadyForUser($user) {
		if (!$this->isReady()) {
			return false;
		}

		foreach ($this->getEncryptionModules() as $module) {
			/** @var IEncryptionModule $m */
			$m = call_user_func($module['callback']);
			if (!$m->isReadyForUser($user)) {
				return false;
			}
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

		$this->encryptionModules[$id] = [
			'id' => $id,
			'displayName' => $displayName,
			'callback' => $callback,
		];

		$defaultEncryptionModuleId = $this->getDefaultEncryptionModuleId();

		if (empty($defaultEncryptionModuleId)) {
			$this->setDefaultEncryptionModule($id);
		}
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
		if (empty($moduleId)) {
			return $this->getDefaultEncryptionModule();
		}
		if (isset($this->encryptionModules[$moduleId])) {
			return call_user_func($this->encryptionModules[$moduleId]['callback']);
		}
		$message = "Module with ID: $moduleId does not exist.";
		$hint = $this->l->t('Module with ID: %s does not exist. Please enable it in your apps settings or contact your administrator.', [$moduleId]);
		throw new Exceptions\ModuleDoesNotExistsException($message, $hint);
	}

	/**
	 * get default encryption module
	 *
	 * @return \OCP\Encryption\IEncryptionModule
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	protected function getDefaultEncryptionModule() {
		$defaultModuleId = $this->getDefaultEncryptionModuleId();
		if (empty($defaultModuleId)) {
			$message = 'No default encryption module defined';
			throw new Exceptions\ModuleDoesNotExistsException($message);
		}
		if (isset($this->encryptionModules[$defaultModuleId])) {
			return call_user_func($this->encryptionModules[$defaultModuleId]['callback']);
		}
		$message = 'Default encryption module not loaded';
		throw new Exceptions\ModuleDoesNotExistsException($message);
	}

	/**
	 * set default encryption module Id
	 *
	 * @param string $moduleId
	 * @return bool
	 */
	public function setDefaultEncryptionModule($moduleId) {
		try {
			$this->getEncryptionModule($moduleId);
		} catch (\Exception $e) {
			return false;
		}

		$this->config->setAppValue('core', 'default_encryption_module', $moduleId);
		return true;
	}

	/**
	 * get default encryption module Id
	 *
	 * @return string
	 */
	public function getDefaultEncryptionModuleId() {
		return $this->config->getAppValue('core', 'default_encryption_module');
	}

	/**
	 * Add storage wrapper
	 */
	public function setupStorage() {
		// If encryption is disabled and there are no loaded modules it makes no sense to load the wrapper
		if (!empty($this->encryptionModules) || $this->isEnabled()) {
			$encryptionWrapper = new EncryptionWrapper($this->arrayCache, $this, $this->logger);
			Filesystem::addStorageWrapper('oc_encryption', [$encryptionWrapper, 'wrapStorage'], 2);
		}
	}

	public function forceWrapStorage(IMountPoint $mountPoint, IStorage $storage) {
		$encryptionWrapper = new EncryptionWrapper($this->arrayCache, $this, $this->logger);
		return $encryptionWrapper->wrapStorage($mountPoint->getMountPoint(), $storage, $mountPoint, true);
	}


	/**
	 * check if key storage is ready
	 *
	 * @return bool
	 */
	protected function isKeyStorageReady() {
		$rootDir = $this->util->getKeyStorageRoot();

		// the default root is always valid
		if ($rootDir === '') {
			return true;
		}

		// check if key storage is mounted correctly
		if ($this->rootView->file_exists($rootDir . '/' . Storage::KEY_STORAGE_MARKER)) {
			return true;
		}

		return false;
	}
}
