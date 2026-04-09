<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Accounts;

use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\User\Events\UserChangedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserChangedEvent>
 */
class Hooks implements IEventListener {
	public function __construct(
		private LoggerInterface $logger,
		private IAccountManager $accountManager,
	) {
	}

	/**
	 * update accounts table if email address or display name was changed from outside
	 */
	public function changeUserHook(IUser $user, string $feature, $newValue): void {
		$account = $this->accountManager->getAccount($user);

		try {
			switch ($feature) {
				case 'eMailAddress':
					$property = $account->getProperty(IAccountManager::PROPERTY_EMAIL);
					break;
				case 'displayName':
					$property = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);
					break;
			}
		} catch (PropertyDoesNotExistException $e) {
			$this->logger->debug($e->getMessage(), ['exception' => $e]);
			return;
		}

		if (isset($property) && $property->getValue() !== (string)$newValue) {
			$property->setValue($newValue);
			$this->accountManager->updateAccount($account);
		}
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserChangedEvent) {
			return;
		}
		$this->changeUserHook($event->getUser(), $event->getFeature(), $event->getValue());
	}
}
