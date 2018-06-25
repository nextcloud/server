<?php

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace Test\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\Db\ProviderUserAssignmentDao;
use OC\Authentication\TwoFactorAuth\Registry;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class RegistryTest extends TestCase {

	/** @var ProviderUserAssignmentDao|PHPUnit_Framework_MockObject_MockObject */
	private $dao;

	/** @var Registry */
	private $registry;

	protected function setUp() {
		parent::setUp();

		$this->dao = $this->createMock(ProviderUserAssignmentDao::class);

		$this->registry = new Registry($this->dao);
	}

	public function testGetProviderStates() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$state = [
			'twofactor_totp' => true,
		];
		$this->dao->expects($this->once())->method('getState')->willReturn($state);

		$actual = $this->registry->getProviderStates($user);

		$this->assertEquals($state, $actual);
	}

	public function testEnableProvider() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$provider->expects($this->once())->method('getId')->willReturn('p1');
		$this->dao->expects($this->once())->method('persist')->with('p1', 'user123',
			true);

		$this->registry->enableProviderFor($provider, $user);
	}

	public function testDisableProvider() {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$provider->expects($this->once())->method('getId')->willReturn('p1');
		$this->dao->expects($this->once())->method('persist')->with('p1', 'user123',
			false);

		$this->registry->disableProviderFor($provider, $user);
	}

}
