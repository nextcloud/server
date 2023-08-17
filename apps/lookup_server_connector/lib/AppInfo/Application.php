<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\LookupServerConnector\AppInfo;

use Closure;
use OCA\LookupServerConnector\UpdateLookupServer;
use OCP\Accounts\UserUpdatedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'lookup_server_connector';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerEventListeners']));
	}

	/**
	 * @todo move the OCP events and then move the registration to `register`
	 */
	private function registerEventListeners(IEventDispatcher $dispatcher,
											ContainerInterface $appContainer): void {
		$dispatcher->addListener(UserUpdatedEvent::class, function (UserUpdatedEvent $event) use ($appContainer) {
			/** @var UpdateLookupServer $updateLookupServer */
			$updateLookupServer = $appContainer->get(UpdateLookupServer::class);
			$updateLookupServer->userUpdated($event->getUser());
		});
	}
}
