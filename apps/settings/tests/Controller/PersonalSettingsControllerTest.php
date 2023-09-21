<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan C. Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Private Maker <privatemaker@posteo.net>
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
namespace OCA\Settings\Tests\Controller;

use OCA\Settings\Controller\PersonalSettingsController;
use OCA\Settings\Settings\Personal\ServerDevNotice;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class PersonalSettingsControllerTest
 *
 * @group DB
 *
 * @package Tests\Settings\Controller
 */
class PersonalSettingsControllerTest extends TestCase {

	/** @var PersonalSettingsController */
	private $personalSettingsController;
	/** @var IRequest|MockObject */
	private $request;
	/** @var INavigationManager|MockObject */
	private $navigationManager;
	/** @var IManager|MockObject */
	private $settingsManager;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var IGroupManager|MockObject */

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->settingsManager = $this->createMock(IManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->personalSettingsController = new PersonalSettingsController(
			'settings',
			$this->request,
			$this->navigationManager,
			$this->settingsManager,
			$this->userSession,
		);

		$user = \OC::$server->getUserManager()->createUser($this->adminUid, 'mylongrandompassword');
		\OC_User::setUserId($user->getUID());
		// \OC::$server->getGroupManager()->createGroup('admin')->addUser($user);
	}

	protected function tearDown(): void {
		// \OC::$server->getUserManager()->get($this->adminUid)->delete();

		parent::tearDown();
	}

	public function testIndex() {
		$user = $this->createMock(IUser::class);
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$user->method('getUID')->willReturn('user123');
		$this->settingsManager
			->expects($this->once())
			->method('getPersonalSections')
			->willReturn([]);

		$idx = $this->personalSettingsController->index('test');

		$expected = new TemplateResponse('settings', 'settings/personal', [
			'forms' => ['personal' => []],
			'content' => ''
		]);
		$this->assertEquals($expected, $idx);
	}
}
