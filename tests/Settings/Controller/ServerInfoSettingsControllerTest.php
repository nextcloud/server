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

namespace Settings\Controller;

use OC\Settings\Theming\ServerInfo;
use OC\Settings\Controller\ServerInfoSettingsController;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * This class provides tests for the server info settings controller.
 */
class ServerInfoSettingsControllerTest extends TestCase {

	/**
	 * @var IConfig|MockObject
	 */
	private $config;

	/**
	 * @var ServerInfoSettingsController
	 */
	private $controller;

	/**
	 * Does the test setup.
	 */
	protected function setUp() {
		parent::setUp();

		$request = $this->getMockBuilder(IRequest::class)->getMock();
		/* @var IRequest|MockObject $request */
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->controller = new ServerInfoSettingsController(
			'settings',
			$request,
			$this->config
		);
	}

	/**
	 * Tests that the handler passes the params to the config.
	 */
	public function testStoreServerInfo() {

		$location = 'test-location';
		$provider = 'test-provider';
		$providerWebsite = 'https://example.com/';
		$providerPrivacyLink = 'https://example.com/privacy';
		$adminContact = 'testuser';

		$this->config->expects($this->once())
			->method('setSystemValues')
			->with([
				ServerInfo::SETTING_LOCATION => $location,
				ServerInfo::SETTING_PROVIDER => $provider,
				ServerInfo::SETTING_PROVIDER_WEBSITE => $providerWebsite,
				ServerInfo::SETTING_PROVIDER_PRIVACY_LINK => $providerPrivacyLink,
				ServerInfo::SETTING_PROVIDER_ADMIN_CONTACT => $adminContact,
			]);

		$this->controller->storeServerInfo(
			$location,
			$provider,
			$providerWebsite,
			$providerPrivacyLink,
			$adminContact
		);

	}

}
