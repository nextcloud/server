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

namespace OC\Settings\Controller;

use OC\Settings\Theming\ServerInfo;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;

/**
 * This controller handles server info settings requests.
 */
class ServerInfoSettingsController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * ServerInfoSettingsController constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct($appName, IRequest $request, IConfig $config) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	public function storeServerInfo(
		string $location,
		string $provider,
		string $providerWebsite,
		string $providerPrivacyLink,
		string $adminContact
	): void {
		$configs = [
			ServerInfo::SETTING_LOCATION => $location,
			ServerInfo::SETTING_PROVIDER => $provider,
			ServerInfo::SETTING_PROVIDER_WEBSITE => $providerWebsite,
			ServerInfo::SETTING_PROVIDER_PRIVACY_LINK => $providerPrivacyLink,
			ServerInfo::SETTING_PROVIDER_ADMIN_CONTACT => $adminContact
		];
		$this->config->setSystemValues($configs);
	}

}
