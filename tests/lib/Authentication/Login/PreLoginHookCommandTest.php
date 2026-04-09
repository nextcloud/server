<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\PreLoginHookCommand;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\User\Events\BeforeUserLoggedInEvent;
use PHPUnit\Framework\MockObject\MockObject;

class PreLoginHookCommandTest extends ALoginTestCommand {
	private IEventDispatcher&MockObject $eventDispatcher;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->cmd = new PreLoginHookCommand(
			$this->eventDispatcher,
		);
	}

	public function testProcess(): void {
		$data = $this->getBasicLoginData();
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(function (BeforeUserLoggedInEvent $event): void {
				$this->assertEquals($this->username, $event->getUsername());
				$this->assertEquals($this->password, $event->getPassword());
			}));

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
