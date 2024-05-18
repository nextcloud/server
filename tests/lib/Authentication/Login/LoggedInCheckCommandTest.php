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

use OC\Authentication\Login\LoggedInCheckCommand;
use OC\Core\Controller\LoginController;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class LoggedInCheckCommandTest extends ALoginCommandTest {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventDispatcher|MockObject */
	private $dispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);

		$this->cmd = new LoggedInCheckCommand(
			$this->logger,
			$this->dispatcher
		);
	}

	public function testProcessSuccessfulLogin() {
		$data = $this->getLoggedInLoginData();

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessFailedLogin() {
		$data = $this->getFailedLoginData();
		$this->logger->expects($this->once())
			->method('warning');

		$result = $this->cmd->process($data);

		$this->assertFalse($result->isSuccess());
		$this->assertSame(LoginController::LOGIN_MSG_INVALIDPASSWORD, $result->getErrorMessage());
	}
}
