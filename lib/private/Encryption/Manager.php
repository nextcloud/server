<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Encryption;

use OC\Encryption\Exceptions\ModuleAlreadyExistsException;
use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\Encryption\Keys\Storage;
use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Memcache\ArrayCache;
use OC\ServiceUnavailableException;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	/**
	 * @var array<string, array{callback: callable(): IEncryptionModule, displayName: string, id: string}>
	 */
	protected array $encryptionModules;

	public function __construct(
		protected readonly IConfig $config,
		protected readonly IAppConfig $appConfig,
		protected readonly LoggerInterface $logger,
		protected readonly IL10N $l,
		protected readonly IRootFolder $rootFolder,
		protected readonly Util $util,
		protected readonly ArrayCache $arrayCache,
	) {
		$this->encryptionModules = [];
	}

	#[\Override]
	public function isEnabled(): bool {
		$installed = $this->config->getSystemValueBool('installed', false);
		if (!$installed) {
			return false;
		}

		return $this->appConfig->getValueBool('core', 'encryption_enabled');
	}

	/**
	 * Check if new encryption is ready
	 *
	 * @throws ServiceUnavailableException
	 */
	public function isReady(): bool {
		if ($this->isKeyStorageReady() === false) {
			throw new ServiceUnavailableException('Key Storage is not ready');
		}

		return true;
	}

	public function isReadyForUser(string $user): bool {
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

	#[\Override]
	public function registerEncryptionModule(string $id, string $displayName, callable $callback): void {
		if (isset($this->encryptionModules[$id])) {
			throw new ModuleAlreadyExistsException($id, $displayName);
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

	#[\Override]
	public function unregisterEncryptionModule(string $moduleId): void {
		unset($this->encryptionModules[$moduleId]);
	}

	#[\Override]
	public function getEncryptionModules(): array {
		return $this->encryptionModules;
	}

	#[\Override]
	public function getEncryptionModule(string $moduleId = ''): IEncryptionModule {
		if (empty($moduleId)) {
			return $this->getDefaultEncryptionModule();
		}
		if (isset($this->encryptionModules[$moduleId])) {
			return call_user_func($this->encryptionModules[$moduleId]['callback']);
		}
		$message = "Module with ID: $moduleId does not exist.";
		$hint = $this->l->t('Module with ID: %s does not exist. Please enable it in your apps settings or contact your administrator.', [$moduleId]);
		throw new ModuleDoesNotExistsException($message, $hint);
	}

	/**
	 * Get default encryption module
	 *
	 * @throws Exceptions\ModuleDoesNotExistsException
	 */
	protected function getDefaultEncryptionModule(): IEncryptionModule {
		$defaultModuleId = $this->getDefaultEncryptionModuleId();
		if (empty($defaultModuleId)) {
			$message = 'No default encryption module defined';
			throw new ModuleDoesNotExistsException($message);
		}
		if (isset($this->encryptionModules[$defaultModuleId])) {
			return call_user_func($this->encryptionModules[$defaultModuleId]['callback']);
		}
		$message = 'Default encryption module not loaded';
		throw new ModuleDoesNotExistsException($message);
	}

	#[\Override]
	public function setDefaultEncryptionModule(string $moduleId): bool {
		try {
			$this->getEncryptionModule($moduleId);
		} catch (\Exception $e) {
			return false;
		}

		$this->appConfig->setValueString('core', 'default_encryption_module', $moduleId);
		return true;
	}

	#[\Override]
	public function getDefaultEncryptionModuleId(): string {
		return $this->appConfig->getValueString('core', 'default_encryption_module');
	}

	/**
	 * Add storage wrapper
	 */
	public function setupStorage(): void {
		// If encryption is disabled and there are no loaded modules it makes no sense to load the wrapper
		if ($this->encryptionModules !== [] && $this->isEnabled()) {
			$encryptionWrapper = new EncryptionWrapper($this->arrayCache, $this, $this->logger);
			Filesystem::addStorageWrapper('oc_encryption', $encryptionWrapper->wrapStorage(...), 2);
		}
	}

	public function forceWrapStorage(IMountPoint $mountPoint, IStorage $storage): Encryption|IStorage {
		$encryptionWrapper = new EncryptionWrapper($this->arrayCache, $this, $this->logger);
		return $encryptionWrapper->wrapStorage($mountPoint->getMountPoint(), $storage, $mountPoint, true);
	}

	/**
	 * Check if key storage is ready
	 */
	protected function isKeyStorageReady(): bool {
		$rootDir = $this->util->getKeyStorageRoot();

		// the default root is always valid
		if ($rootDir === '') {
			return true;
		}

		// check if key storage is mounted correctly
		return $this->rootFolder->nodeExists($rootDir . '/' . Storage::KEY_STORAGE_MARKER);
	}
}
