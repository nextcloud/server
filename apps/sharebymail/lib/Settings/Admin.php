<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ShareByMail\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	public function __construct(
		private SettingsManager $settingsManager,
		private IL10N $l,
		private IInitialState $initialState,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$this->initialState->provideInitialState('sendPasswordMail', $this->settingsManager->sendPasswordByMail());
		$this->initialState->provideInitialState('replyToInitiator', $this->settingsManager->replyToInitiator());

		return new TemplateResponse('sharebymail', 'settings-admin', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'sharing';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 40;
	}

	public function getName(): ?string {
		return $this->l->t('Share by mail');
	}

	public function getAuthorizedAppConfig(): array {
		return [
			'sharebymail' => ['s/(sendpasswordmail|replyToInitiator)/'],
		];
	}
}
