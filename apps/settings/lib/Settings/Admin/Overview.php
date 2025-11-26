<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\ServerVersion;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class Overview implements IDelegatedSettings {
	public function __construct(
		private ServerVersion $serverVersion,
		private IConfig $config,
		private IL10N $l,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		Util::addScript('settings', 'vue-settings-admin-overview');
		$this->initialState->provideInitialState('setup-checks-section', [
			'sectionDocsUrl' => $this->urlGenerator->linkToDocs('admin-warnings'),
			'installationGuidesDocsUrl' => $this->urlGenerator->linkToDocs('admin-install'),
			'loggingSectionUrl' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'logging']),
		]);

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
