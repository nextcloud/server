<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use OC\Authentication\TwoFactorAuth\Registry;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @template-implements IEventListener<\OCP\User\Events\UserDeletedEvent>
 */
class UserDeletedStoreCleanupListener implements IEventListener {
	/** @var Registry */
	private $registry;

	public function __construct(Registry $registry) {
		$this->registry = $registry;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$this->registry->deleteUserData($event->getUser());
	}
}
