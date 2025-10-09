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
use OCA\Settings\Service\ConflictException;
use OCP\AppFramework\Db\DoesNotExistException;
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

	public function testCreateSuccessWhenNoDuplicateExists(): void {
		$groupId = 'testgroup';
		$class = 'TestClass';

		// Mock that no existing assignment is found (throws DoesNotExistException)
		$this->mapper->expects($this->once())
			->method('findByGroupIdAndClass')
			->with($groupId, $class)
			->willThrowException(new DoesNotExistException('Not found'));

		// Mock the successful creation
		$expectedGroup = new AuthorizedGroup();
		$expectedGroup->setGroupId($groupId);
		$expectedGroup->setClass($class);
		$expectedGroup->setId(123);

		$this->mapper->expects($this->once())
			->method('insert')
			->willReturn($expectedGroup);

		$result = $this->service->create($groupId, $class);

		$this->assertInstanceOf(AuthorizedGroup::class, $result);
		$this->assertEquals($groupId, $result->getGroupId());
		$this->assertEquals($class, $result->getClass());
	}

	public function testCreateThrowsConflictExceptionWhenDuplicateExists(): void {
		$groupId = 'testgroup';
		$class = 'TestClass';

		// Mock that an existing assignment is found
		$existingGroup = new AuthorizedGroup();
		$existingGroup->setGroupId($groupId);
		$existingGroup->setClass($class);
		$existingGroup->setId(42);

		$this->mapper->expects($this->once())
			->method('findByGroupIdAndClass')
			->with($groupId, $class)
			->willReturn($existingGroup);

		// Mapper insert should never be called when duplicate exists
		$this->mapper->expects($this->never())
			->method('insert');

		$this->expectException(ConflictException::class);
		$this->expectExceptionMessage('Group is already assigned to this class');

		$this->service->create($groupId, $class);
	}

	public function testCreateAllowsDifferentGroupsSameClass(): void {
		$groupId1 = 'testgroup1';
		$groupId2 = 'testgroup2';
		$class = 'TestClass';

		// Mock that no duplicate exists for group1
		$this->mapper->expects($this->exactly(2))
			->method('findByGroupIdAndClass')
			->willReturnCallback(function ($groupId, $classArg) use ($groupId1, $groupId2, $class) {
				$this->assertContains($groupId, [$groupId1, $groupId2]);
				$this->assertEquals($class, $classArg);
				throw new DoesNotExistException('Not found');
			});

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

		// Mock that no duplicate exists for either class
		$this->mapper->expects($this->exactly(2))
			->method('findByGroupIdAndClass')
			->willReturnCallback(function ($groupIdArg, $class) use ($groupId, $class1, $class2) {
				$this->assertEquals($groupId, $groupIdArg);
				$this->assertContains($class, [$class1, $class2]);
				throw new DoesNotExistException('Not found');
			});

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
