<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
namespace OCA\Federation\Settings;

use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	private TrustedServers $trustedServers;
	private IL10N $l;

	public function __construct(TrustedServers $trustedServers, IL10N $l) {
		$this->trustedServers = $trustedServers;
		$this->l = $l;
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
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 30;
	}

	public function getName(): ?string {
		return $this->l->t("Trusted servers");
	}

	public function getAuthorizedAppConfig(): array {
		return []; // Handled by custom controller
	}
}
