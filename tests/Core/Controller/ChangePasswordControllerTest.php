<?php
/**
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace Tests\Core\Controller;

use OC\HintException;
use OC\Settings\Controller\ChangePasswordController;
use OC\User\Session;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;

class ChangePasswordControllerTest extends \Test\TestCase {

	/** @var string */
	private $userId = 'currentUser';

	/** @var IUserManager */
	private $userManager;

	/** @var Session */
	private $userSession;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IAppManager */
	private $appManager;

	/** @var IL10N */
	private $l;

	/** @var ChangePasswordController */
	private $controller;

	public function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')->getMock();
		$this->userSession = $this->getMockBuilder('OC\User\Session')->disableOriginalConstructor()->getMock();
		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')->getMock();
		$this->appManager = $this->getMockBuilder('OCP\App\IAppManager')->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')->getMock();

		$this->l->method('t')->will($this->returnArgument(0));

		$request = $this->getMockBuilder('OCP\IRequest')->getMock();

		$this->controller = new ChangePasswordController(
			'core',
			$request,
			$this->userId,
			$this->userManager,
			$this->userSession,
			$this->groupManager,
			$this->appManager,
			$this->l
		);
	}

	public function testChangePersonalPasswordWrongPassword() {
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->userId, 'old')
			->willReturn(false);

		$expects = [
			'status' => 'error',
			'data' => [
				'message' => 'Wrong password',
			],
		];

		$res = $this->controller->changePersonalPassword('old', 'new');

		$this->assertEquals($expects, $res->getData());
	}

	public function testChangePersonalPasswordCommonPassword() {
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->userId, 'old')
			->willReturn($user);

		$user->expects($this->once())
			->method('setPassword')
			->with('new')
			->will($this->throwException(new HintException('Common password')));

		$expects = [
			'status' => 'error',
			'data' => [
				'message' => 'Common password',
			],
		];

		$res = $this->controller->changePersonalPassword('old', 'new');

		$this->assertEquals($expects, $res->getData());
	}

	public function testChangePersonalPasswordNoNewPassword() {
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->userId, 'old')
			->willReturn($user);

		$expects = [
			'status' => 'error',
		];

		$res = $this->controller->changePersonalPassword('old');

		$this->assertEquals($expects, $res->getData());
	}

	public function testChangePersonalPasswordCantSetPassword() {
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->userId, 'old')
			->willReturn($user);

		$user->expects($this->once())
			->method('setPassword')
			->with('new')
			->willReturn(false);

		$expects = [
			'status' => 'error',
		];

		$res = $this->controller->changePersonalPassword('old', 'new');

		$this->assertEquals($expects, $res->getData());
	}

	public function testChangePersonalPassword() {
		$user = $this->getMockBuilder('OCP\IUser')->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->userId, 'old')
			->willReturn($user);

		$user->expects($this->once())
			->method('setPassword')
			->with('new')
			->willReturn(true);

		$this->userSession->expects($this->once())
			->method('updateSessionTokenPassword')
			->with('new');

		$expects = [
			'status' => 'success',
			'data' => [
				'message' => 'Saved',
			],
		];

		$res = $this->controller->changePersonalPassword('old', 'new');

		$this->assertEquals($expects, $res->getData());
	}
}
