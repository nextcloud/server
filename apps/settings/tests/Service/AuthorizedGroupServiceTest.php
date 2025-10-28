<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Service;

use OC\Settings\AuthorizedGroup;
use OC\Settings\AuthorizedGroupMapper;
use OCA\Settings\Service\AuthorizedGroupService;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AuthorizedGroupServiceTest extends TestCase {

	private AuthorizedGroupMapper&MockObject $mapper;
	private AuthorizedGroupService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->mapper = $this->createMock(AuthorizedGroupMapper::class);
		$this->service = new AuthorizedGroupService($this->mapper);
	}

	public function testCreateAllowsDifferentGroupsSameClass(): void {
		$groupId1 = 'testgroup1';
		$groupId2 = 'testgroup2';
		$class = 'TestClass';

		$expectedGroup1 = new AuthorizedGroup();
		$expectedGroup1->setGroupId($groupId1);
		$expectedGroup1->setClass($class);
		$expectedGroup1->setId(123);

		$expectedGroup2 = new AuthorizedGroup();
		$expectedGroup2->setGroupId($groupId2);
		$expectedGroup2->setClass($class);
		$expectedGroup2->setId(124);

		$this->mapper->expects($this->exactly(2))
			->method('insert')
			->willReturnOnConsecutiveCalls($expectedGroup1, $expectedGroup2);

		// Both creations should succeed
		$result1 = $this->service->create($groupId1, $class);
		$this->assertEquals($groupId1, $result1->getGroupId());
		$this->assertEquals($class, $result1->getClass());

		$result2 = $this->service->create($groupId2, $class);
		$this->assertEquals($groupId2, $result2->getGroupId());
		$this->assertEquals($class, $result2->getClass());
	}

	public function testCreateAllowsSameGroupDifferentClasses(): void {
		$groupId = 'testgroup';
		$class1 = 'TestClass1';
		$class2 = 'TestClass2';

		$expectedGroup1 = new AuthorizedGroup();
		$expectedGroup1->setGroupId($groupId);
		$expectedGroup1->setClass($class1);
		$expectedGroup1->setId(123);

		$expectedGroup2 = new AuthorizedGroup();
		$expectedGroup2->setGroupId($groupId);
		$expectedGroup2->setClass($class2);
		$expectedGroup2->setId(124);

		$this->mapper->expects($this->exactly(2))
			->method('insert')
			->willReturnOnConsecutiveCalls($expectedGroup1, $expectedGroup2);

		// Both creations should succeed
		$result1 = $this->service->create($groupId, $class1);
		$result2 = $this->service->create($groupId, $class2);

		$this->assertEquals($groupId, $result1->getGroupId());
		$this->assertEquals($groupId, $result2->getGroupId());
		$this->assertEquals($class1, $result1->getClass());
		$this->assertEquals($class2, $result2->getClass());
	}
}
