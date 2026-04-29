<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Personal\Security;

use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OC\Authentication\WebAuthn\Manager;
use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IInitialStateService;
use OCP\Settings\ISettings;

class WebAuthn implements ISettings {

	public function __construct(
		private PublicKeyCredentialMapper $mapper,
		private string $userId,
		private IInitialStateService $initialStateService,
		private Manager $manager,
	) {
	}

	#[\Override]
	public function getForm() {
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'webauthn-devices',
			$this->mapper->findAllForUid($this->userId)
		);

		return new TemplateResponse('settings', 'settings/personal/security/webauthn');
	}

	#[\Override]
	public function getSection(): ?string {
		if (!$this->manager->isWebAuthnAvailable()) {
			return null;
		}

		return 'security';
	}

	#[\Override]
	public function getPriority(): int {
		return 20;
	}
}
