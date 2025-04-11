<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ServerVersion;
use OCP\Settings\IDelegatedSettings;

class Overview implements IDelegatedSettings {
	public function __construct(
		private ServerVersion $serverVersion,
		private IConfig $config,
		private IL10N $l,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			'checkForWorkingWellKnownSetup' => $this->config->getSystemValue('check_for_working_wellknown_setup', true),
			'version' => $this->serverVersion->getHumanVersion(),
		];

		return new TemplateResponse('settings', 'settings/admin/overview', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'overview';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 10;
	}

	public function getName(): ?string {
		return $this->l->t('Security & setup checks');
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
