<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Test\Sharing;

use OCA\Sharing\Tests\TestSharePermissionCategoryType;
use OCA\Sharing\Tests\TestSharePermissionType;
use OCA\Sharing\Tests\TestSharePropertyType;
use OCA\Sharing\Tests\TestShareRecipientType;
use OCA\Sharing\Tests\TestShareRecipientType2;
use OCA\Sharing\Tests\TestShareSourceType;
use OCA\Sharing\Tests\TestShareSourceType2;
use OCP\Server;
use OCP\Sharing\IRegistry;
use RuntimeException;
use Test\TestCase;

final class RegistryTest extends TestCase {
	private IRegistry $registry;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(IRegistry::class);
		$this->registry->clear();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testClear(): void {
		$this->registry->registerSourceType(new TestShareSourceType([]));
		$this->registry->registerRecipientType(new TestShareRecipientType([], [], []));
		$this->registry->registerPropertyType(new TestSharePropertyType(['']));
		$this->registry->registerPropertyTypeCompatibleWithSourceType(TestSharePropertyType::class, TestShareSourceType::class);
		$this->registry->registerPropertyTypeCompatibleWithRecipientType(TestSharePropertyType::class, TestShareRecipientType::class);
		$this->registry->registerPermissionCategoryType(new TestSharePermissionCategoryType());
		$this->registry->registerPermissionType(TestShareSourceType::class, new TestSharePermissionType());

		$this->registry->clear();

		$this->assertEquals([], $this->registry->getSourceTypes());
		$this->assertEquals([], $this->registry->getRecipientTypes());
		$this->assertEquals([], $this->registry->getPropertyTypes());
		$this->assertEquals([], $this->registry->getSourceTypesCompatibleWithPropertyType(TestSharePropertyType::class));
		$this->assertEquals([], $this->registry->getRecipientTypesCompatibleWithPropertyType(TestSharePropertyType::class));
		$this->assertEquals([], $this->registry->getPermissionCategoryTypes());
		$this->assertEquals([], $this->registry->getPermissionTypes());
	}

	public function testRegisterSourceType(): void {
		$sourceType = new TestShareSourceType([]);
		$this->registry->registerSourceType($sourceType);

		$this->assertEquals([$sourceType::class => $sourceType], $this->registry->getSourceTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerSourceType($sourceType);
	}

	public function testRegisterRecipientType(): void {
		$recipientType = new TestShareRecipientType([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$this->assertEquals([$recipientType::class => $recipientType], $this->registry->getRecipientTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerRecipientType($recipientType);
	}

	public function testRegisterProperty(): void {
		$propertyType = new TestSharePropertyType(['']);
		$this->registry->registerPropertyType($propertyType);

		$this->assertEquals([$propertyType::class => $propertyType], $this->registry->getPropertyTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPropertyType($propertyType);
	}

	public function testRegisterPropertyCompatibleWithSourceType(): void {
		$sourceType = new TestShareSourceType([]);
		$this->registry->registerSourceType($sourceType);

		$propertyType = new TestSharePropertyType(['']);
		$this->registry->registerPropertyType($propertyType);
		$this->registry->registerPropertyTypeCompatibleWithSourceType($propertyType::class, $sourceType::class);
		$this->registry->registerPropertyTypeCompatibleWithSourceType($propertyType::class, $sourceType::class);

		$this->assertEquals([$sourceType::class], $this->registry->getSourceTypesCompatibleWithPropertyType($propertyType::class));

		$this->registry->registerPropertyTypeCompatibleWithSourceType($propertyType::class, TestShareSourceType2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getSourceTypesCompatibleWithPropertyType($propertyType::class);
	}

	public function testRegisterPropertyCompatibleWithRecipientType(): void {
		$recipientType = new TestShareRecipientType([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$propertyType = new TestSharePropertyType(['']);
		$this->registry->registerPropertyType($propertyType);
		$this->registry->registerPropertyTypeCompatibleWithRecipientType($propertyType::class, $recipientType::class);
		$this->registry->registerPropertyTypeCompatibleWithRecipientType($propertyType::class, $recipientType::class);

		$this->assertEquals([$recipientType::class], $this->registry->getRecipientTypesCompatibleWithPropertyType($propertyType::class));

		$this->registry->registerPropertyTypeCompatibleWithRecipientType($propertyType::class, TestShareRecipientType2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getRecipientTypesCompatibleWithPropertyType($propertyType::class);
	}

	public function testRegisterPermissionCategory(): void {
		$permissionCategoryType = new TestSharePermissionCategoryType();
		$this->registry->registerPermissionCategoryType($permissionCategoryType);

		$this->assertEquals([$permissionCategoryType::class => $permissionCategoryType], $this->registry->getPermissionCategoryTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPermissionCategoryType($permissionCategoryType);
	}

	public function testRegisterPermission(): void {
		$sourceType = new TestShareSourceType([]);
		$this->registry->registerSourceType($sourceType);

		$permissionCategoryType = new TestSharePermissionCategoryType();
		$this->registry->registerPermissionCategoryType($permissionCategoryType);

		$permissionType = new TestSharePermissionType();
		$this->registry->registerPermissionType($sourceType::class, $permissionType);

		$this->assertEquals([$permissionType::class => $permissionType], $this->registry->getPermissionTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPermissionType($sourceType::class, $permissionType);
	}
}
