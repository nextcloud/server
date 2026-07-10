<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Traits;

use OC\Encryption\EncryptionWrapper;
use OC\Files\SetupManager;
use OC\Memcache\ArrayCache;
use OCA\Encryption\AppInfo\Application;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;
use OCP\App\IAppManager;
use OCP\Encryption\IManager;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Enables encryption
 */
trait EncryptionTrait {
	// from MountProviderTrait
	abstract protected function registerStorageWrapper($name, $wrapper);

	// from phpunit
	abstract protected static function markTestSkipped(string $message = ''): void;
	abstract protected static function assertTrue($condition, string $message = ''): void;

	private $encryptionWasEnabled;

	private $originalEncryptionModule;

	private IUserManager $userManagerEncTrait;
	private ISetupManager $setupManagerEncTrait;
	private ?IConfig $config = null;
	private IAppConfig $appConfig;
	private Application $encryptionApp;

	protected function loginWithEncryption($user = '') {
		\OC_User::setUserId('');
		// needed for fully logout
		Server::get(IUserSession::class)->setUser(null);

		$this->setupManagerEncTrait->tearDown();

		\OC_User::setUserId($user);
		$this->postLogin();
		$this->setupManagerEncTrait->setupForUser($this->userManagerEncTrait->get($user));
		if ($this->userManagerEncTrait->userExists($user)) {
			Server::get(IRootFolder::class)->getUserFolder($user);
		}
	}

	protected function setupForUser(string $name, string $password): void {
		$this->setupManagerEncTrait->tearDown();
		$this->setupManagerEncTrait->setupForUser($this->userManagerEncTrait->get($name));

		$container = $this->encryptionApp->getContainer();
		/** @var KeyManager $keyManager */
		$keyManager = $container->get(KeyManager::class);
		/** @var Setup $userSetup */
		$userSetup = $container->get(Setup::class);
		$userSetup->setupUser($name, $password);
		$encryptionManager = $container->get(IManager::class);
		$this->encryptionApp->setUp($encryptionManager);
		$keyManager->init($name, $password);
		$this->invokePrivate($keyManager, 'keyUid', [$name]);
	}

	protected function postLogin(): void {
		$encryptionWrapper = new EncryptionWrapper(
			new ArrayCache(),
			Server::get(\OCP\Encryption\IManager::class),
			Server::get(LoggerInterface::class)
		);

		$this->registerStorageWrapper('oc_encryption', [$encryptionWrapper, 'wrapStorage']);
	}

	protected function setUpEncryptionTrait(): void {
		$isReady = Server::get(\OCP\Encryption\IManager::class)->isReady();
		if (!$isReady) {
			$this->markTestSkipped('Encryption not ready');
		}

		$this->userManagerEncTrait = Server::get(IUserManager::class);
		$this->setupManagerEncTrait = Server::get(SetupManager::class);

		Server::get(IAppManager::class)->loadApp('encryption');

		$this->encryptionApp = new Application([]);

		$this->config = Server::get(IConfig::class);
		$this->appConfig = Server::get(IAppConfig::class);
		$this->encryptionWasEnabled = $this->appConfig->getValueBool('core', 'encryption_enabled');
		$this->originalEncryptionModule = $this->appConfig->getValueString('core', 'default_encryption_module');
		$this->config->setAppValue('core', 'default_encryption_module', Encryption::ID);
		$this->appConfig->setValueBool('core', 'encryption_enabled', true);
		$this->assertTrue(Server::get(\OCP\Encryption\IManager::class)->isEnabled());
	}

	protected function tearDownEncryptionTrait(): void {
		if ($this->config) {
			$this->appConfig->setValueBool('core', 'encryption_enabled', $this->encryptionWasEnabled);
			$this->config->setAppValue('core', 'default_encryption_module', $this->originalEncryptionModule);
			$this->config->deleteAppValue('encryption', 'useMasterKey');
		}
	}
}
