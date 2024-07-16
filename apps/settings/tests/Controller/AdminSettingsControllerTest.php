<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Controller;

use OCA\Settings\Controller\AdminSettingsController;
use OCA\Settings\Settings\Personal\ServerDevNotice;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
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
	/** @var IRequest|MockObject */
	private $request;
	/** @var INavigationManager|MockObject */
	private $navigationManager;
	/** @var IManager|MockObject */
	private $settingsManager;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var ISubAdmin|MockObject */
	private $subAdmin;
	/** @var IDeclarativeManager|MockObject */
	private $declarativeSettingsManager;
	/** @var IInitialState|MockObject */
	private $initialState;
	/** @var string */
	private $adminUid = 'lololo';

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->settingsManager = $this->createMock(IManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->subAdmin = $this->createMock(ISubAdmin::class);
		$this->declarativeSettingsManager = $this->createMock(IDeclarativeManager::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->adminSettingsController = new AdminSettingsController(
			'settings',
			$this->request,
			$this->navigationManager,
			$this->settingsManager,
			$this->userSession,
			$this->groupManager,
			$this->subAdmin,
			$this->declarativeSettingsManager,
			$this->initialState,
		);

		$user = \OC::$server->getUserManager()->createUser($this->adminUid, 'mylongrandompassword');
		\OC_User::setUserId($user->getUID());
		\OC::$server->getGroupManager()->createGroup('admin')->addUser($user);
	}

	protected function tearDown(): void {
		\OC::$server->getUserManager()->get($this->adminUid)->delete();
		\OC_User::setUserId(null);
		\OC::$server->getUserSession()->setUser(null);

		parent::tearDown();
	}

	public function testIndex() {
		$user = $this->createMock(IUser::class);
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$user->method('getUID')->willReturn('user123');
		$this->groupManager
			->method('isAdmin')
			->with('user123')
			->willReturn(true);
		$this->subAdmin
			->method('isSubAdmin')
			->with($user)
			->willReturn(false);
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
			->method('getAllowedAdminSettings')
			->with('test')
			->willReturn([5 => $this->createMock(ServerDevNotice::class)]);
		$this->declarativeSettingsManager
			->expects($this->any())
			->method('getFormIDs')
			->with($user, 'admin', 'test')
			->willReturn([]);

		$idx = $this->adminSettingsController->index('test');

		$expected = new TemplateResponse('settings', 'settings/frame', [
			'forms' => ['personal' => [], 'admin' => []],
			'content' => ''
		]);
		$this->assertEquals($expected, $idx);
	}
}
