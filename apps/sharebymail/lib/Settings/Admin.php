<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Nicolas SIMIDE <2083596+dems54@users.noreply.github.com>
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
namespace OCA\ShareByMail\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	private SettingsManager $settingsManager;
	private IL10N $l;
	private IInitialState $initialState;

	public function __construct(SettingsManager $settingsManager, IL10N $l, IInitialState $initialState) {
		$this->settingsManager = $settingsManager;
		$this->l = $l;
		$this->initialState = $initialState;
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
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
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
