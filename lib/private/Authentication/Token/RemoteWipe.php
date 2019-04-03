<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Authentication\Token;

use BadMethodCallException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\WipeTokenException;
use OCP\Activity\IManager as IActivityManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;
use OCP\Notification\IManager as INotificationManager;

class RemoteWipe {

	/** @var IProvider */
	private $tokenProvider;

	/** @var IActivityManager */
	private $activityManager;

	/** @var INotificationManager */
	private $notificationManager;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var ILogger */
	private $logger;

	public function __construct(IProvider $tokenProvider,
								IActivityManager $activityManager,
								INotificationManager $notificationManager,
								ITimeFactory $timeFactory,
								ILogger $logger) {
		$this->tokenProvider = $tokenProvider;
		$this->activityManager = $activityManager;
		$this->notificationManager = $notificationManager;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
	}

	/**
	 * @param string $token
	 *
	 * @return bool whether wiping was started
	 * @throws InvalidTokenException
	 *
	 */
	public function start(string $token): bool {
		try {
			$this->tokenProvider->getToken($token);

			// We expect a WipedTokenException here. If we reach this point this
			// is an ordinary token
			return false;
		} catch (WipeTokenException $e) {
			// Expected -> continue below
		}

		$dbToken = $e->getToken();

		$this->logger->info("user " . $dbToken->getUID() . " started a remote wipe");
		$this->sendNotification('remote_wipe_start', $e->getToken());
		$this->publishActivity('remote_wipe_start', $e->getToken());

		return true;
	}

	/**
	 * @param string $token
	 *
	 * @return bool whether wiping could be finished
	 * @throws InvalidTokenException
	 */
	public function finish(string $token): bool {
		try {
			$this->tokenProvider->getToken($token);

			// We expect a WipedTokenException here. If we reach this point this
			// is an ordinary token
			return false;
		} catch (WipeTokenException $e) {
			// Expected -> continue below
		}

		$dbToken = $e->getToken();

		$this->tokenProvider->invalidateToken($token);

		$this->logger->info("user " . $dbToken->getUID() . " finished a remote wipe");
		$this->sendNotification('remote_wipe_finish', $e->getToken());
		$this->publishActivity('remote_wipe_finish', $e->getToken());

		return true;
	}

	private function publishActivity(string $event, IToken $token): void {
		$activity = $this->activityManager->generateEvent();
		$activity->setApp('core')
			->setType('security')
			->setAuthor($token->getUID())
			->setAffectedUser($token->getUID())
			->setSubject($event, [
				'name' => $token->getName(),
			]);
		try {
			$this->activityManager->publish($activity);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('could not publish activity', ['app' => 'core']);
			$this->logger->logException($e, ['app' => 'core']);
		}
	}

	private function sendNotification(string $event, IToken $token): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('auth')
			->setUser($token->getUID())
			->setDateTime($this->timeFactory->getDateTime())
			->setObject('token', $token->getId())
			->setSubject($event, [
				'name' => $token->getName(),
			]);
		$this->notificationManager->notify($notification);
	}

}
