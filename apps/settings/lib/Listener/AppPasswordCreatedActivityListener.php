<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\Settings\Listener;

use BadMethodCallException;
use OC\Authentication\Events\AppPasswordCreatedEvent;
use OCA\Settings\Activity\Provider;
use OCP\Activity\IManager as IActivityManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<\OC\Authentication\Events\AppPasswordCreatedEvent>
 */
class AppPasswordCreatedActivityListener implements IEventListener {
	/** @var IActivityManager */
	private $activityManager;

	/** @var IUserSession */
	private $userSession;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IActivityManager $activityManager,
								IUserSession $userSession,
								LoggerInterface $logger) {
		$this->activityManager = $activityManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof AppPasswordCreatedEvent)) {
			return;
		}

		$activity = $this->activityManager->generateEvent();
		$activity->setApp('settings')
			->setType('security')
			->setAffectedUser($event->getToken()->getUID())
			->setAuthor($this->userSession->getUser() ? $this->userSession->getUser()->getUID() : '')
			->setSubject(Provider::APP_TOKEN_CREATED, ['name' => $event->getToken()->getName()])
			->setObject('app_token', $event->getToken()->getId());

		try {
			$this->activityManager->publish($activity);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('Could not publish activity: ' . $e->getMessage(), [
				'exception' => $e
			]);
		}
	}
}
