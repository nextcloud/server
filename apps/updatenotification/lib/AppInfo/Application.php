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

use OCA\UpdateNotification\Notification\Notifier;
use OCA\UpdateNotification\UpdateChecker;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Util;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('updatenotification', []);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerNotifierService(Notifier::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IConfig $config,
			IUserSession $userSession,
			IAppManager $appManager,
			IGroupManager $groupManager,
			IAppContainer $appContainer,
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
					$updateChecker = $appContainer->get(UpdateChecker::class);
				} catch (QueryException $e) {
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
