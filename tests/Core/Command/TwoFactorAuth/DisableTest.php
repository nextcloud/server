<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
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
namespace Test\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Command\TwoFactorAuth\Disable;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DisableTest extends TestCase {

	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	private $manager;

	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var Disable */
	private $command;

	public function setUp() {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->command = new Disable($this->manager, $this->userManager);
	}

	public function testDisableSuccess() {
		$user = $this->createMock(IUser::class);

		$input = $this->createMock(InputInterface::class);
		$output = $this->createMock(OutputInterface::class);

		$input->method('getArgument')
			->with($this->equalTo('uid'))
			->willReturn('user');

		$this->userManager->method('get')
			->with('user')
			->willReturn($user);

		$this->manager->expects($this->once())
			->method('disableTwoFactorAuthentication')
			->with($this->equalTo($user));

		$output->expects($this->once())
			->method('writeln')
			->with('Two-factor authentication disabled for user user');

		$this->invokePrivate($this->command, 'execute', [$input, $output]);
	}

	public function testEnableFail() {
		$input = $this->createMock(InputInterface::class);
		$output = $this->createMock(OutputInterface::class);

		$input->method('getArgument')
			->with($this->equalTo('uid'))
			->willReturn('user');

		$this->userManager->method('get')
			->with('user')
			->willReturn(null);

		$this->manager->expects($this->never())
			->method($this->anything());

		$output->expects($this->once())
			->method('writeln')
			->with('<error>Invalid UID</error>');

		$this->invokePrivate($this->command, 'execute', [$input, $output]);
	}
}
