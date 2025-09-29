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

	/** @var PublicKeyCredentialMapper */
	private $mapper;

	/** @var Manager */
	private $manager;

	public function __construct(
		PublicKeyCredentialMapper $mapper,
		private string $userId,
		private IInitialStateService $initialStateService,
		Manager $manager,
	) {
		$this->mapper = $mapper;
		$this->manager = $manager;
	}

	public function getForm() {
		$this->initialStateService->provideInitialState(
			Application::APP_ID,
			'webauthn-devices',
			$this->mapper->findAllForUid($this->userId)
		);

		return new TemplateResponse('settings', 'settings/personal/security/webauthn');
	}

	public function getSection(): ?string {
		if (!$this->manager->isWebAuthnAvailable()) {
			return null;
		}

		return 'security';
	}

	public function getPriority(): int {
		return 20;
	}
}
