<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\User\Session;

class CompleteLoginCommand extends ALoginCommand {
	/** @var Session */
	private $userSession;

	public function __construct(Session $userSession) {
		$this->userSession = $userSession;
	}

	public function process(LoginData $loginData): LoginResult {
		$this->userSession->completeLogin(
			$loginData->getUser(),
			[
				'loginName' => $loginData->getUsername(),
				'password' => $loginData->getPassword(),
			]
		);

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
