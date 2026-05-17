<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\User\Events\BeforeUserLoggedInEvent;

class PreLoginHookCommand extends ALoginCommand {
	public function __construct(
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	#[\Override]
	public function process(LoginData $loginData): LoginResult {
		$this->eventDispatcher->dispatchTyped(new BeforeUserLoggedInEvent(
			$loginData->getUsername(),
			$loginData->getPassword(),
		));

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
