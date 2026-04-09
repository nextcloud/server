<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use OC\Authentication\Events\LoginFailed;
use OCP\Authentication\Events\AnyLoginFailedEvent;
use OCP\Authentication\Events\LoginFailedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserManager;
use OCP\Util;

/**
 * @template-implements IEventListener<LoginFailed>
 */
class LoginFailedListener implements IEventListener {
	public function __construct(
		private IEventDispatcher $dispatcher,
		private IUserManager $userManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoginFailed)) {
			return;
		}

		$this->dispatcher->dispatchTyped(new AnyLoginFailedEvent($event->getLoginName(), $event->getPassword()));

		$uid = $event->getLoginName();
		Util::emitHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			['uid' => &$uid]
		);
		if ($this->userManager->userExists($uid)) {
			$this->dispatcher->dispatchTyped(new LoginFailedEvent($uid));
		}
	}
}
