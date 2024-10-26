<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

abstract class ALoginCommand {
	/** @var ALoginCommand */
	protected $next;

	public function setNext(ALoginCommand $next): ALoginCommand {
		$this->next = $next;
		return $next;
	}

	protected function processNextOrFinishSuccessfully(LoginData $loginData): LoginResult {
		if ($this->next !== null) {
			return $this->next->process($loginData);
		} else {
			return LoginResult::success($loginData);
		}
	}

	abstract public function process(LoginData $loginData): LoginResult;
}
