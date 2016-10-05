<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 *
 */

namespace Tests\Settings;


use OC\Settings\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Middleware;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

/**
 * Class ApplicationTest
 *
 * @package Tests\Settings
 * @group DB
 */
class ApplicationTest extends TestCase {
	/** @var \OC\Settings\Application */
	protected $app;

	/** @var \OCP\AppFramework\IAppContainer */
	protected $container;

	protected function setUp() {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName() {
		$this->app = new Application();
		$this->assertEquals('settings', $this->container->getAppName());
	}

	public function dataContainerQuery() {
		return array(
			array('AdminSettingsController', Controller::class),
			array('AppSettingsController', Controller::class),
			array('AuthSettingsController', Controller::class),
			// Needs session: array('CertificateController', Controller::class),
			array('CheckSetupController', Controller::class),
			array('EncryptionController', Controller::class),
			array('GroupsController', Controller::class),
			array('LogSettingsController', Controller::class),
			array('MailSettingsController', Controller::class),
			array('SecuritySettingsController', Controller::class),
			array('UsersController', Controller::class),

			array('SubadminMiddleware', Middleware::class),
		);
	}

	/**
	 * @dataProvider dataContainerQuery
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected) {
		$this->assertTrue($this->container->query($service) instanceof $expected);
	}

	public function dataContainerQueryRequiresSession() {
		return array(
			array('CertificateController', Controller::class),
		);
	}

	/**
	 * @dataProvider dataContainerQueryRequiresSession
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQueryRequiresSession($service, $expected) {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('test');

		$session = $this->createMock(IUserSession::class);
		$session->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->overwriteService('UserSession', $session);
		$this->assertTrue($this->container->query($service) instanceof $expected);
		$this->restoreService('UserSession');
	}
}
