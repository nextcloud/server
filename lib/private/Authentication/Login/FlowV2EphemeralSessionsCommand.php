<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Login;

use OC\Core\Controller\ClientFlowLoginV2Controller;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ISession;
use OCP\IURLGenerator;

class FlowV2EphemeralSessionsCommand extends ALoginCommand {
	public function __construct(
		private ISession $session,
		private IURLGenerator $urlGenerator,
		private ITimeFactory $timeFactory,
	) {
	}

	public function process(LoginData $loginData): LoginResult {
		$loginV2GrantRoute = $this->urlGenerator->linkToRoute('core.ClientFlowLoginV2.grantPage');
		if (str_starts_with($loginData->getRedirectUrl() ?? '', $loginV2GrantRoute)) {
			$this->session->set(ClientFlowLoginV2Controller::EPHEMERAL_NAME, $this->timeFactory->getTime());
		}

		return $this->processNextOrFinishSuccessfully($loginData);
	}
}
