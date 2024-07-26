<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\AppInfo;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Crypto\DecryptAll;
use OCA\Encryption\Crypto\EncryptAll;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\HookManager;
use OCA\Encryption\Hooks\UserHooks;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCA\Encryption\Session;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'encryption';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		\OCP\Util::addScript(self::APP_ID, 'encryption');

		$context->injectFn(function (IManager $encryptionManager) use ($context) {
			if (!($encryptionManager instanceof \OC\Encryption\Manager)) {
				return;
			}

			if (!$encryptionManager->isReady()) {
				return;
			}

			$context->injectFn($this->registerEncryptionModule(...));
			$context->injectFn($this->registerHooks(...));
			$context->injectFn($this->setUp(...));
		});
	}

	public function setUp(IManager $encryptionManager) {
		if ($encryptionManager->isEnabled()) {
			/** @var Setup $setup */
			$setup = $this->getContainer()->query(Setup::class);
			$setup->setupSystem();
		}
	}

	/**
	 * register hooks
	 */
	public function registerHooks(IConfig $config) {
		if (!$config->getSystemValueBool('maintenance')) {
			$container = $this->getContainer();
			$server = $container->getServer();
			// Register our hooks and fire them.
			$hookManager = new HookManager();

			$hookManager->registerHook([
				new UserHooks($container->query(KeyManager::class),
					$server->getUserManager(),
					$server->get(LoggerInterface::class),
					$container->query(Setup::class),
					$server->getUserSession(),
					$container->query(Util::class),
					$container->query(Session::class),
					$container->query(Crypt::class),
					$container->query(Recovery::class))
			]);

			$hookManager->fireHooks();
		} else {
			// Logout user if we are in maintenance to force re-login
			$this->getContainer()->getServer()->getUserSession()->logout();
		}
	}

	public function registerEncryptionModule(IManager $encryptionManager) {
		$container = $this->getContainer();

		$encryptionManager->registerEncryptionModule(
			Encryption::ID,
			Encryption::DISPLAY_NAME,
			function () use ($container) {
				return new Encryption(
					$container->query(Crypt::class),
					$container->query(KeyManager::class),
					$container->query(Util::class),
					$container->query(Session::class),
					$container->query(EncryptAll::class),
					$container->query(DecryptAll::class),
					$container->getServer()->get(LoggerInterface::class),
					$container->getServer()->getL10N($container->getAppName())
				);
			});
	}
}
