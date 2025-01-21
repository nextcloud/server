<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Listener;

use OCA\Files_External\Lib\Auth\Password\LoginCredentials;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\ICredentialsManager;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserLoggedInEvent;

/** @template-implements IEventListener<PasswordUpdatedEvent|UserLoggedInEvent> */
class StorePasswordListener implements IEventListener {
	public function __construct(
		private ICredentialsManager $credentialsManager,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserLoggedInEvent && !$event instanceof PasswordUpdatedEvent) {
			return;
		}

		if ($event instanceof UserLoggedInEvent && $event->isTokenLogin()) {
			return;
		}

		$storedCredentials = $this->credentialsManager->retrieve($event->getUser()->getUID(), LoginCredentials::CREDENTIALS_IDENTIFIER);

		if (!$storedCredentials) {
			return;
		}

		$newCredentials = $storedCredentials;
		$shouldUpdate = false;

		if (($storedCredentials['password'] ?? null) !== $event->getPassword() && $event->getPassword() !== null) {
			$shouldUpdate = true;
			$newCredentials['password'] = $event->getPassword();
		}

		if ($event instanceof UserLoggedInEvent && ($storedCredentials['user'] ?? null) !== $event->getLoginName()) {
			$shouldUpdate = true;
			$newCredentials['user'] = $event->getLoginName();
		}

		if ($shouldUpdate) {
			$this->credentialsManager->store($event->getUser()->getUID(), LoginCredentials::CREDENTIALS_IDENTIFIER, $newCredentials);
		}
	}
}
