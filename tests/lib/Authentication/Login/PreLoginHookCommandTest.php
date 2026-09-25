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

class PreLoginHookCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(PreLoginHookCommand::class);
	}

	public function testProcess(): void {
		$data = $this->getBasicLoginData();
		$this->mocks[IEventDispatcher::class]->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(function (BeforeUserLoggedInEvent $event): bool {
				$this->assertEquals($this->username, $event->getUsername());
				$this->assertEquals($this->password, $event->getPassword());
				return true;
			}));

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
