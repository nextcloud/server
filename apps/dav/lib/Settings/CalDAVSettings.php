<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author François Freitag <mail@franek.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
