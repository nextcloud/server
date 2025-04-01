<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\AppInfo;

use OC\Core\Events\BeforePasswordResetEvent;
use OC\Core\Events\PasswordResetEvent;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Crypto\DecryptAll;
use OCA\Encryption\Crypto\EncryptAll;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Listeners\UserEventsListener;
use OCA\Encryption\Session;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Encryption\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\User\Events\BeforePasswordUpdatedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use OCP\User\Events\UserLoggedOutEvent;
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

		$context->injectFn(function (IManager $encryptionManager) use ($context): void {
			if (!($encryptionManager instanceof \OC\Encryption\Manager)) {
				return;
			}

			if (!$encryptionManager->isReady()) {
				return;
			}

			$context->injectFn($this->registerEncryptionModule(...));
			$context->injectFn($this->registerEventListeners(...));
			$context->injectFn($this->setUp(...));
		});
	}

	public function setUp(IManager $encryptionManager) {
		if ($encryptionManager->isEnabled()) {
			/** @var Setup $setup */
			$setup = $this->getContainer()->get(Setup::class);
			$setup->setupSystem();
		}
	}

	public function registerEventListeners(IConfig $config, IEventDispatcher $eventDispatcher, IManager $encryptionManager): void {
		if (!$encryptionManager->isEnabled()) {
			return;
		}

		if ($config->getSystemValueBool('maintenance')) {
			// Logout user if we are in maintenance to force re-login
			$this->getContainer()->get(IUserSession::class)->logout();
			return;
		}

		// No maintenance so register all events
		$eventDispatcher->addServiceListener(UserCreatedEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(BeforePasswordUpdatedEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(PasswordUpdatedEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(BeforePasswordResetEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(PasswordResetEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(UserLoggedInEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(UserLoggedInWithCookieEvent::class, UserEventsListener::class);
		$eventDispatcher->addServiceListener(UserLoggedOutEvent::class, UserEventsListener::class);
	}

	public function registerEncryptionModule(IManager $encryptionManager) {
		$container = $this->getContainer();

		$encryptionManager->registerEncryptionModule(
			Encryption::ID,
			Encryption::DISPLAY_NAME,
			function () use ($container) {
				return new Encryption(
					$container->get(Crypt::class),
					$container->get(KeyManager::class),
					$container->get(Util::class),
					$container->get(Session::class),
					$container->get(EncryptAll::class),
					$container->get(DecryptAll::class),
					$container->get(LoggerInterface::class),
					$container->get(IL10N::class),
				);
			});
	}
}
