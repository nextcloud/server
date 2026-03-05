<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Group;

use OC\Group\Group;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IHideFromCollaborationBackend;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

abstract class HideFromCollaborationBackendTest extends ABackend implements IHideFromCollaborationBackend {

}

class HideFromCollaborationTest extends TestCase {

	private IUserManager&MockObject $userManager;
	private IEventDispatcher&MockObject $dispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
	}


	public function testHideFromCollaboration(): void {
		// Arrange
		$backend1 = $this->createMock(HideFromCollaborationBackendTest::class);
		$backend1->method('hideGroup')
			->willReturn(false);
		$backend2 = $this->createMock(HideFromCollaborationBackendTest::class);
		$backend2->method('hideGroup')
			->willReturn(true);
		$group = new Group('group1', [$backend1, $backend2], $this->dispatcher, $this->userManager);

		// Act
		$result = $group->hideFromCollaboration();

		// Assert
		$this->assertTrue($result);
	}
}
