<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Traits;

use OC\Encryption\EncryptionWrapper;
use OC\Files\SetupManager;
use OC\Memcache\ArrayCache;
use OCA\Encryption\AppInfo\Application;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;
use OCP\Encryption\IManager;
use OCP\IUserManager;
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

	/** @var IUserManager */
	private $userManager;
	/** @var SetupManager */
	private $setupManager;

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * @var \OCA\Encryption\AppInfo\Application
	 */
	private $encryptionApp;

	protected function loginWithEncryption($user = '') {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		// needed for fully logout
		\OC::$server->getUserSession()->setUser(null);

		$this->setupManager->tearDown();

		\OC_User::setUserId($user);
		$this->postLogin();
		\OC_Util::setupFS($user);
		if ($this->userManager->userExists($user)) {
			\OC::$server->getUserFolder($user);
		}
	}

	protected function setupForUser($name, $password) {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($this->userManager->get($name));

		$container = $this->encryptionApp->getContainer();
		/** @var KeyManager $keyManager */
		$keyManager = $container->query(KeyManager::class);
		/** @var Setup $userSetup */
		$userSetup = $container->query(Setup::class);
		$userSetup->setupUser($name, $password);
		$encryptionManager = $container->query(IManager::class);
		$this->encryptionApp->setUp($encryptionManager);
		$keyManager->init($name, $password);
	}

	protected function postLogin() {
		$encryptionWrapper = new EncryptionWrapper(
			new ArrayCache(),
			\OC::$server->getEncryptionManager(),
			\OC::$server->get(LoggerInterface::class)
		);

		$this->registerStorageWrapper('oc_encryption', [$encryptionWrapper, 'wrapStorage']);
	}

	protected function setUpEncryptionTrait() {
		$isReady = \OC::$server->getEncryptionManager()->isReady();
		if (!$isReady) {
			$this->markTestSkipped('Encryption not ready');
		}

		$this->userManager = \OC::$server->get(IUserManager::class);
		$this->setupManager = \OC::$server->get(SetupManager::class);

		\OC_App::loadApp('encryption');

		$this->encryptionApp = new Application([], $isReady);

		$this->config = \OC::$server->getConfig();
		$this->encryptionWasEnabled = $this->config->getAppValue('core', 'encryption_enabled', 'no');
		$this->originalEncryptionModule = $this->config->getAppValue('core', 'default_encryption_module');
		$this->config->setAppValue('core', 'default_encryption_module', \OCA\Encryption\Crypto\Encryption::ID);
		$this->config->setAppValue('core', 'encryption_enabled', 'yes');
		$this->assertTrue(\OC::$server->getEncryptionManager()->isEnabled());
	}

	protected function tearDownEncryptionTrait() {
		if ($this->config) {
			$this->config->setAppValue('core', 'encryption_enabled', $this->encryptionWasEnabled);
			$this->config->setAppValue('core', 'default_encryption_module', $this->originalEncryptionModule);
			$this->config->deleteAppValue('encryption', 'useMasterKey');
		}
	}
}
