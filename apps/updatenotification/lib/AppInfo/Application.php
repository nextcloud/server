<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UpdateNotification\AppInfo;

use OCA\UpdateNotification\Listener\AppUpdateEventListener;
use OCA\UpdateNotification\Listener\BeforeTemplateRenderedEventListener;
use OCA\UpdateNotification\Notification\AppUpdateNotifier;
use OCA\UpdateNotification\Notification\Notifier;
use OCA\UpdateNotification\UpdateChecker;
use OCP\App\Events\AppUpdateEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Util;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_NAME = 'updatenotification';

	public function __construct() {
		parent::__construct(self::APP_NAME, []);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerNotifierService(Notifier::class);
		$context->registerNotifierService(AppUpdateNotifier::class);

		$context->registerEventListener(AppUpdateEvent::class, AppUpdateEventListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedEventListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IConfig $config,
			IUserSession $userSession,
			IAppManager $appManager,
			IGroupManager $groupManager,
			ContainerInterface $container,
			LoggerInterface $logger,
		): void {
			if ($config->getSystemValue('updatechecker', true) !== true) {
				// Updater check is disabled
				return;
			}

			$user = $userSession->getUser();
			if (!$user instanceof IUser) {
				// Nothing to do for guests
				return;
			}

			if (!$appManager->isEnabledForUser('notifications')
				&& $groupManager->isAdmin($user->getUID())) {
				try {
					$updateChecker = $container->get(UpdateChecker::class);
				} catch (ContainerExceptionInterface $e) {
					$logger->error($e->getMessage(), ['exception' => $e]);
					return;
				}

				if ($updateChecker->getUpdateState() !== []) {
					Util::addScript(self::APP_NAME, 'update-notification-legacy');
					$updateChecker->setInitialState();
				}
			}
		});
	}
}
