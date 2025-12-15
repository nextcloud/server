<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Integration;

use OC\Settings\AuthorizedGroup;
use OC\Settings\AuthorizedGroupMapper;
use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Service\ConflictException;
use OCP\AppFramework\Db\DoesNotExistException;
use Test\TestCase;

/**
 * Integration test for duplicate prevention in AuthorizedGroupService
 * This test verifies the complete flow of duplicate detection and prevention
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class DuplicateAssignmentIntegrationTest extends TestCase {

	private AuthorizedGroupService $service;
	private AuthorizedGroupMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		// Use real mapper for integration testing
		$this->mapper = \OCP\Server::get(AuthorizedGroupMapper::class);
		$this->service = new AuthorizedGroupService($this->mapper);
	}

	protected function tearDown(): void {
		// Clean up any test data
		try {
			$allGroups = $this->mapper->findAll();
			foreach ($allGroups as $group) {
				if (str_starts_with($group->getGroupId(), 'test_')
					|| str_starts_with($group->getClass(), 'TestClass')) {
					$this->mapper->delete($group);
				}
			}
		} catch (\Exception $e) {
			// Ignore cleanup errors
		}
		parent::tearDown();
	}

	public function testDuplicateAssignmentPrevention(): void {
		$groupId = 'test_duplicate_group';
		$class = 'TestClass\\DuplicateTest';

		// First assignment should succeed
		$result1 = $this->service->create($groupId, $class);
		$this->assertInstanceOf(AuthorizedGroup::class, $result1);
		$this->assertEquals($groupId, $result1->getGroupId());
		$this->assertEquals($class, $result1->getClass());
		$this->assertNotNull($result1->getId());

		// Second assignment of same group to same class should throw ConflictException
		$this->expectException(ConflictException::class);
		$this->expectExceptionMessage('Group is already assigned to this class');

		$this->service->create($groupId, $class);
	}

	public function testDifferentGroupsSameClassAllowed(): void {
		$groupId1 = 'test_group_1';
		$groupId2 = 'test_group_2';
		$class = 'TestClass\\MultiGroup';

		// Both assignments should succeed
		$result1 = $this->service->create($groupId1, $class);
		$result2 = $this->service->create($groupId2, $class);

		$this->assertEquals($groupId1, $result1->getGroupId());
		$this->assertEquals($groupId2, $result2->getGroupId());
		$this->assertEquals($class, $result1->getClass());
		$this->assertEquals($class, $result2->getClass());
		$this->assertNotEquals($result1->getId(), $result2->getId());
	}

	public function testSameGroupDifferentClassesAllowed(): void {
		$groupId = 'test_multi_class_group';
		$class1 = 'TestClass\\First';
		$class2 = 'TestClass\\Second';

		// Both assignments should succeed
		$result1 = $this->service->create($groupId, $class1);
		$result2 = $this->service->create($groupId, $class2);

		$this->assertEquals($groupId, $result1->getGroupId());
		$this->assertEquals($groupId, $result2->getGroupId());
		$this->assertEquals($class1, $result1->getClass());
		$this->assertEquals($class2, $result2->getClass());
		$this->assertNotEquals($result1->getId(), $result2->getId());
	}

	public function testCreateAfterDelete(): void {
		$groupId = 'test_recreate_group';
		$class = 'TestClass\\Recreate';

		// Create initial assignment
		$result1 = $this->service->create($groupId, $class);
		$initialId = $result1->getId();

		// Delete the assignment
		$this->service->delete($initialId);

		// Verify it's deleted by trying to find it
		$this->expectException(\OCP\AppFramework\Db\DoesNotExistException::class);
		try {
			$this->service->find($initialId);
		} catch (\OCA\Settings\Service\NotFoundException $e) {
			// Expected - now create the same assignment again, which should succeed
			$result2 = $this->service->create($groupId, $class);

			$this->assertEquals($groupId, $result2->getGroupId());
			$this->assertEquals($class, $result2->getClass());
			$this->assertNotEquals($initialId, $result2->getId());
			return;
		}

		$this->fail('Expected NotFoundException when finding deleted group');
	}

	/**
	 * Test the mapper's findByGroupIdAndClass method behavior with duplicates
	 */
	public function testMapperFindByGroupIdAndClassBehavior(): void {
		$groupId = 'test_mapper_group';
		$class = 'TestClass\\MapperTest';

		// Initially should throw DoesNotExistException
		$this->expectException(DoesNotExistException::class);
		$this->mapper->findByGroupIdAndClass($groupId, $class);
	}

	/**
	 * Test that mapper returns existing record after creation
	 */
	public function testMapperFindsExistingRecord(): void {
		$groupId = 'test_existing_group';
		$class = 'TestClass\\Existing';

		// Create the record first
		$created = $this->service->create($groupId, $class);

		// Now mapper should find it
		$found = $this->mapper->findByGroupIdAndClass($groupId, $class);

		$this->assertEquals($created->getId(), $found->getId());
		$this->assertEquals($groupId, $found->getGroupId());
		$this->assertEquals($class, $found->getClass());
	}
}
