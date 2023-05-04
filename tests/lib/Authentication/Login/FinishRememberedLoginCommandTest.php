<?php

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\FinishRememberedLoginCommand;
use OC\User\Session;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class FinishRememberedLoginCommandTest extends ALoginCommandTest {
	/** @var Session|MockObject */
	private $userSession;
	/** @var IConfig|MockObject */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->config = $this->createMock(IConfig::class);

		$this->cmd = new FinishRememberedLoginCommand(
			$this->userSession,
			$this->config
		);
	}

	public function testProcessNotRememberedLogin() {
		$data = $this->getLoggedInLoginData();
		$data->setRememberLogin(false);
		$this->userSession->expects($this->never())
			->method('createRememberMeToken');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcess() {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('auto_logout', false)
			->willReturn(false);
		$this->userSession->expects($this->once())
			->method('createRememberMeToken')
			->with($this->user);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessNotRemeberedLoginWithAutologout() {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('auto_logout', false)
			->willReturn(true);
		$this->userSession->expects($this->never())
			->method('createRememberMeToken');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
