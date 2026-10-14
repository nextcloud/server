<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Login;

use OC\User\LastInteractiveLogin;

class RecordInteractiveLoginCommand extends ALoginCommand {
	public function __construct(
		private LastInteractiveLogin $lastInteractiveLogin,
	) {
	}

	#[\Override]
	public function process(LoginData $loginData): LoginResult {
		$this->lastInteractiveLogin->record($loginData->getUser());

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
