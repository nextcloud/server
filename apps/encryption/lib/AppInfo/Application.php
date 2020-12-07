<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\AppInfo;

use Closure;
use OC\Encryption\Manager;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\HookManager;
use OCA\Encryption\Hooks\UserHooks;
use OCA\Encryption\Users\Setup;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\Encryption\IManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Util;

class Application extends App implements IBootstrap {

	public const APP_ID = 'encryption';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		Util::addscript(self::APP_ID, 'encryption');

		$encryptionManager = $context->getServerContainer()->get(Manager::class);
		$encryptionSystemReady = $encryptionManager->isReady();

		if ($encryptionSystemReady) {
			$context->injectFn(Closure::fromCallable([$this, 'registerEncryptionModule']));
			$context->injectFn(Closure::fromCallable([$this, 'registerHooks']));
			$context->injectFn(Closure::fromCallable([$this, 'setUp']));
		}
	}

	private function setUp(IManager $encryptionManager, Setup $setup): void {
		if (!$encryptionManager->isEnabled()) {
			return;
		}

		$setup->setupSystem();
	}

	private function registerHooks(IConfig $config, IAppContainer $container) {
		if (!$config->getSystemValueBool('maintenance')) {
			// Register our hooks and fire them.
			$hookManager = new HookManager();
			$hookManager->registerHook([
				$container->get(UserHooks::class)
			]);
			$hookManager->fireHooks();
		} else {
			// Logout user if we are in maintenance to force re-login
			/** @var IUserSession $userSession */
			$userSession = $container->get(IUserSession::class);
			$userSession->logout();
		}
	}

	private function registerEncryptionModule(IManager $encryptionManager, IAppContainer $container) {
		$encryptionManager->registerEncryptionModule(
			Encryption::ID,
			Encryption::DISPLAY_NAME,
			function () use ($container) {
				return $container->get(Encryption::class);
			});
	}
}
