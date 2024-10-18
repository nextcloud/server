<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Federation\Settings;

use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	public function __construct(
		private TrustedServers $trustedServers,
		private IL10N $l,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			'trustedServers' => $this->trustedServers->getServers(),
		];

		return new TemplateResponse('federation', 'settings-admin', $parameters, '');
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
		return 30;
	}

	public function getName(): ?string {
		return $this->l->t('Trusted servers');
	}

	public function getAuthorizedAppConfig(): array {
		return []; // Handled by custom controller
	}
}
