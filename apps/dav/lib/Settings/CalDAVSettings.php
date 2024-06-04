<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Settings;

use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;

class CalDAVSettings implements IDelegatedSettings {

	/** @var IConfig */
	private $config;

	/** @var IInitialState */
	private $initialState;

	private IURLGenerator $urlGenerator;

	private const defaults = [
		'sendInvitations' => 'yes',
		'generateBirthdayCalendar' => 'yes',
		'sendEventReminders' => 'yes',
		'sendEventRemindersToSharedUsers' => 'yes',
		'sendEventRemindersPush' => 'yes',
	];

	/**
	 * CalDAVSettings constructor.
	 *
	 * @param IConfig $config
	 * @param IInitialState $initialState
	 */
	public function __construct(IConfig $config, IInitialState $initialState, IURLGenerator $urlGenerator) {
		$this->config = $config;
		$this->initialState = $initialState;
		$this->urlGenerator = $urlGenerator;
	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('userSyncCalendarsDocUrl', $this->urlGenerator->linkToDocs('user-sync-calendars'));
		foreach (self::defaults as $key => $default) {
			$value = $this->config->getAppValue(Application::APP_ID, $key, $default);
			$this->initialState->provideInitialState($key, $value === 'yes');
		}
		return new TemplateResponse(Application::APP_ID, 'settings-admin-caldav');
	}

	/**
	 * @return string
	 */
	public function getSection() {
		return 'groupware';
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return 10;
	}

	public function getName(): ?string {
		return null; // Only setting in this section
	}

	public function getAuthorizedAppConfig(): array {
		return [
			'dav' => ['/(' . implode('|', array_keys(self::defaults)) . ')/']
		];
	}
}
