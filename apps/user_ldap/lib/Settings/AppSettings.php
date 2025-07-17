<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class AppSettings implements IDelegatedSettings {

	public function __construct(
		protected IL10N $l,
		protected IInitialState $initialState,
		protected IAppConfig $appConfig,
	) {
	}

	public function getName(): ?string {
		return $this->l->t('Configuration-independent settings');
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('config', [
			'backend_mark_remnants_as_disabled' => $this->appConfig->getValueBool('user_ldap', 'backend_mark_remnants_as_disabled'),
		]);

		return new TemplateResponse('user_ldap', 'app-settings');
	}

	public function getSection(): string {
		return 'ldap';
	}

	public function getPriority(): int {
		return 9;
	}
}
