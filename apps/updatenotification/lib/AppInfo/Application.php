<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			LoggerInterface $logger) {
			if ($config->getSystemValue('updatechecker', true) !== true) {
				// Updater check is disabled
				return;
			}

			$user = $userSession->getUser();
			if (!$user instanceof IUser) {
				// Nothing to do for guests
				return;
			}

			if (!$appManager->isEnabledForUser('notifications') &&
				$groupManager->isAdmin($user->getUID())) {
				try {
					$updateChecker = $container->get(UpdateChecker::class);
				} catch (ContainerExceptionInterface $e) {
					$logger->error($e->getMessage(), ['exception' => $e]);
					return;
				}

				if ($updateChecker->getUpdateState() !== []) {
					Util::addScript('updatenotification', 'legacy-notification');
					\OC_Hook::connect('\OCP\Config', 'js', $updateChecker, 'populateJavaScriptVariables');
				}
			}
		});
	}
}
