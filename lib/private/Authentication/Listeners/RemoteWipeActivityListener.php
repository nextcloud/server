<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use BadMethodCallException;
use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Token\IToken;
use OCP\Activity\IManager as IActvityManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<\OC\Authentication\Events\ARemoteWipeEvent>
 */
class RemoteWipeActivityListener implements IEventListener {
	/** @var IActvityManager */
	private $activityManager;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IActvityManager $activityManager,
		LoggerInterface $logger) {
		$this->activityManager = $activityManager;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof RemoteWipeStarted) {
			$this->publishActivity('remote_wipe_start', $event->getToken());
		} elseif ($event instanceof RemoteWipeFinished) {
			$this->publishActivity('remote_wipe_finish', $event->getToken());
		}
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
			$this->logger->warning('could not publish activity', [
				'app' => 'core',
				'exception' => $e,
			]);
		}
	}
}
