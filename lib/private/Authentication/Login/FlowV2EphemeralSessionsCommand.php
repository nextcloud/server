<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Core\Controller\ClientFlowLoginV2Controller;
use OCP\ISession;

class FlowV2EphemeralSessionsCommand extends ALoginCommand {
	public function __construct(
		private ISession $session,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		if (str_starts_with($loginData->getRedirectUrl() ?? '', '/login/v2/grant')) {
			$this->session->set(ClientFlowLoginV2Controller::EPHEMERAL_NAME, true);
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
