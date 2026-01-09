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
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class AdminSettingsControllerTest
 *
 *
 * @package Tests\Settings\Controller
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class AdminSettingsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private INavigationManager&MockObject $navigationManager;
	private IManager&MockObject $settingsManager;
	private IUserSession&MockObject $userSession;
	private IGroupManager&MockObject $groupManager;
	private ISubAdmin&MockObject $subAdmin;
	private IDeclarativeManager&MockObject $declarativeSettingsManager;
	private IInitialState&MockObject $initialState;

	private string $adminUid = 'lololo';
	private AdminSettingsController $adminSettingsController;

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

		$user = Server::get(IUserManager::class)->createUser($this->adminUid, 'mylongrandompassword');
		\OC_User::setUserId($user->getUID());
		Server::get(IGroupManager::class)->createGroup('admin')->addUser($user);
	}

	protected function tearDown(): void {
		Server::get(IUserManager::class)
			->get($this->adminUid)
			->delete();
		\OC_User::setUserId(null);
		Server::get(IUserSession::class)->setUser(null);

		parent::tearDown();
	}

	public function testIndex(): void {
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

		$form = new TemplateResponse('settings', 'settings/empty');
		$setting = $this->createMock(ServerDevNotice::class);
		$setting->expects(self::any())
			->method('getForm')
			->willReturn($form);
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
			->willReturn([5 => [$setting]]);
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
