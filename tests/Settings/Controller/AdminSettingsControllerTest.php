<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace Tests\Settings\Controller;

use OC\Settings\Admin\TipsTricks;
use OC\Settings\Controller\AdminSettingsController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\Settings\IManager;
use Test\TestCase;

/**
 * Class AdminSettingsControllerTest
 *
 * @group DB
 *
 * @package Tests\Settings\Controller
 */
class AdminSettingsControllerTest extends TestCase {
	/** @var AdminSettingsController */
	private $adminSettingsController;
	/** @var IRequest */
	private $request;
	/** @var INavigationManager */
	private $navigationManager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	private $settingsManager;
	/** @var string */
	private $adminUid = 'lololo';

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->navigationManager = $this->getMockBuilder(INavigationManager::class)->getMock();
		$this->settingsManager = $this->getMockBuilder(IManager::class)->getMock();

		$this->adminSettingsController = new AdminSettingsController(
			'settings',
			$this->request,
			$this->navigationManager,
			$this->settingsManager
		);

		$user = \OC::$server->getUserManager()->createUser($this->adminUid, 'olo');
		\OC_User::setUserId($user->getUID());
		\OC::$server->getGroupManager()->createGroup('admin')->addUser($user);
	}

	public function tearDown() {
		\OC::$server->getUserManager()->get($this->adminUid)->delete();

		parent::tearDown();
	}

	public function testIndex() {
		$this->settingsManager
			->expects($this->once())
			->method('getAdminSections')
			->willReturn([]);
		$this->settingsManager
			->expects($this->once())
			->method('getPersonalSections')
			->willReturn([]);
		$this->settingsManager
			->expects($this->once())
			->method('getAdminSettings')
			->with('test')
			->willReturn([5 => new TipsTricks($this->getMockBuilder(IConfig::class)->getMock())]);

		$expected = new TemplateResponse('settings', 'settings/frame', ['forms' => ['personal' => [], 'admin' => []], 'content' => '']);
		$this->assertEquals($expected, $this->adminSettingsController->index('test'));
	}
}
