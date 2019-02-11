<?php
/**
 * @copyright Copyright (c) 2018 Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Michael Weimann <mail@michael-weimann.eu>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Settings\Theming;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Settings\ISettings;

/**
 * This class describes the server info settings.
 */
class ServerInfo implements ISettings {

	const SETTING_LOCATION = 'serverinfo.location';
	const SETTING_PROVIDER = 'serverinfo.provider';
	const SETTING_PROVIDER_WEBSITE = 'serverinfo.provider.website';
	const SETTING_PROVIDER_PRIVACY_LINK = 'serverinfo.provider.privacylink';
	const SETTING_PROVIDER_ADMIN_CONTACT = 'serverinfo.admincontact';

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * ServerInfo constructor.
	 *
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 */
	public function __construct(IConfig $config, IGroupManager $groupManager) {
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$parameters = [
			'location' => $this->config->getSystemValue(self::SETTING_LOCATION),
			'provider' => $this->config->getSystemValue(self::SETTING_PROVIDER),
			'providerWebsite' => $this->config->getSystemValue(self::SETTING_PROVIDER_WEBSITE),
			'providerPrivacyLink' => $this->config->getSystemValue(self::SETTING_PROVIDER_PRIVACY_LINK),
			'adminUsers' => $this->getAdminListValues(),
			'adminContact' => $this->config->getSystemValue(self::SETTING_PROVIDER_ADMIN_CONTACT),
		];
		return new TemplateResponse('settings', 'settings/admin/server-info', $parameters, '');
	}

	/**
	 * Returns the admin list values.
	 *
	 * @return array[] An array or arrays with the keys 'id' and 'displayName'
	 */
	private function getAdminListValues(): array {
		$adminGroup = $this->groupManager->get('admin');
		$users = $adminGroup->getUsers();

		$users = array_map(function(IUser $user) {
			return [
				'id' => $user->getUID(),
				'displayName' => $user->getDisplayName()
			];
		}, $users);

		usort($your_data, function(array $a, array $b) {
			return strcmp($a['displayName'], $b['displayName']);
		});

		return $users;
	}

	/**
	 * Returns the server info section id.
	 *
	 * @return string
	 */
	public function getSection(): string {
		return 'theming';
	}

	/**
	 * Returns the server info settings priority.
	 *
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 */
	public function getPriority(): int {
		return 10;
	}

}
