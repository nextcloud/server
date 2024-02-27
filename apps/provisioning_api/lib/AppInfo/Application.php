<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\Provisioning_API\AppInfo;

use OC\Group\Manager as GroupManager;
use OCA\Provisioning_API\Capabilities;
use OCA\Provisioning_API\Listener\UserDeletedListener;
use OCA\Provisioning_API\Middleware\ProvisioningApiMiddleware;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {
	public function __construct(array $urlParams = []) {
		parent::__construct('provisioning_api', $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);

		$context->registerService(NewUserMailHelper::class, function (ContainerInterface $c) {
			return new NewUserMailHelper(
				$c->get(Defaults::class),
				$c->get(IURLGenerator::class),
				$c->get(IFactory::class),
				$c->get(IMailer::class),
				$c->get(ISecureRandom::class),
				$c->get(ITimeFactory::class),
				$c->get(IConfig::class),
				$c->get(ICrypto::class),
				Util::getDefaultEmailAddress('no-reply')
			);
		});
		$context->registerService(ProvisioningApiMiddleware::class, function (ContainerInterface $c) {
			$user = $c->get(IUserManager::class)->get($c->get('UserId'));
			$isAdmin = false;
			$isSubAdmin = false;

			if ($user instanceof IUser) {
				$groupManager = $c->get(IGroupManager::class);
				assert($groupManager instanceof GroupManager);
				$isAdmin = $groupManager->isAdmin($user->getUID());
				$isSubAdmin = $groupManager->getSubAdmin()->isSubAdmin($user);
			}

			return new ProvisioningApiMiddleware(
				$c->get(IControllerMethodReflector::class),
				$isAdmin,
				$isSubAdmin
			);
		});
		$context->registerMiddleware(ProvisioningApiMiddleware::class);
		$context->registerCapability(Capabilities::class);
	}

	public function boot(IBootContext $context): void {
	}
}
