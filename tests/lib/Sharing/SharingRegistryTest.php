<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Server;
use OCP\Sharing\ISharingRegistry;
use RuntimeException;
use Test\TestCase;

final class SharingRegistryTest extends TestCase {
	private ISharingRegistry $registry;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(ISharingRegistry::class);
		$this->registry->clear();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testClear(): void {
		$this->registry->registerSourceType(new TestShareSourceType1([]));
		$this->registry->registerRecipientType(new TestShareRecipientType1([], [], []));
		$this->registry->registerPropertyType(new TestSharePropertyType1(['']));
		$this->registry->markPropertyTypeCompatibleWithSourceType(TestSharePropertyType1::class, TestShareSourceType1::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType(TestSharePropertyType1::class, TestShareRecipientType1::class);
		$this->registry->registerPermissionType(TestShareSourceType1::class, new TestSharePermissionType1());
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset1());
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset(TestSharePermissionType1::class, TestSharePermissionPreset1::class);

		$this->registry->clear();

		$this->assertEquals([], $this->registry->getSourceTypes());
		$this->assertEquals([], $this->registry->getRecipientTypes());
		$this->assertEquals([], $this->registry->getPropertyTypes());
		$this->assertEquals([], $this->registry->getPropertyTypeCompatibleSourceTypeClasses());
		$this->assertEquals([], $this->registry->getPropertyTypeCompatibleRecipientTypes());
		$this->assertEquals([], $this->registry->getPermissionTypes());
		$this->assertEquals([], $this->registry->getPermissionPresets());
		$this->assertEquals([], $this->registry->getPermissionTypeCompatiblePermissionPresetClasses());
		$this->assertEquals([], $this->registry->getPermissionPresetCompatiblePermissionTypeClasses());
	}

	public function testRegisterSourceType(): void {
		$sourceType = new TestShareSourceType1([]);
		$this->registry->registerSourceType($sourceType);

		$this->assertEquals([$sourceType::class => $sourceType], $this->registry->getSourceTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerSourceType($sourceType);
	}

	public function testRegisterRecipientType(): void {
		$recipientType = new TestShareRecipientType1([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$this->assertEquals([$recipientType::class => $recipientType], $this->registry->getRecipientTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerRecipientType($recipientType);
	}

	public function testRegisterProperty(): void {
		$sourceType = new TestShareSourceType1([]);
		$this->registry->registerSourceType($sourceType);

		$recipientType = new TestShareRecipientType1([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$propertyType = new TestSharePropertyType1(['']);
		$this->registry->registerPropertyType($propertyType);
		$this->registry->markPropertyTypeCompatibleWithSourceType($propertyType::class, $sourceType::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType($propertyType::class, $recipientType::class);

		$this->assertEquals([$propertyType::class => $propertyType], $this->registry->getPropertyTypes());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPropertyType($propertyType);
	}

	public function testMarkPropertyCompatibleWithSourceType(): void {
		$sourceType = new TestShareSourceType1([]);
		$this->registry->registerSourceType($sourceType);

		$propertyType = new TestSharePropertyType1(['']);
		$this->registry->registerPropertyType($propertyType);
		$this->registry->markPropertyTypeCompatibleWithSourceType($propertyType::class, $sourceType::class);
		$this->registry->markPropertyTypeCompatibleWithSourceType($propertyType::class, $sourceType::class);

		$this->assertEquals([$propertyType::class => [$sourceType::class]], $this->registry->getPropertyTypeCompatibleSourceTypeClasses());

		$this->registry->markPropertyTypeCompatibleWithSourceType($propertyType::class, TestShareSourceType2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getPropertyTypeCompatibleSourceTypeClasses();
	}

	public function testMarkPropertyCompatibleWithRecipientType(): void {
		$recipientType = new TestShareRecipientType1([], [], []);
		$this->registry->registerRecipientType($recipientType);

		$propertyType = new TestSharePropertyType1(['']);
		$this->registry->registerPropertyType($propertyType);
		$this->registry->markPropertyTypeCompatibleWithRecipientType($propertyType::class, $recipientType::class);
		$this->registry->markPropertyTypeCompatibleWithRecipientType($propertyType::class, $recipientType::class);

		$this->assertEquals([$propertyType::class => [$recipientType::class]], $this->registry->getPropertyTypeCompatibleRecipientTypes());

		$this->registry->markPropertyTypeCompatibleWithRecipientType($propertyType::class, TestShareRecipientType2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getPropertyTypeCompatibleRecipientTypes();
	}

	public function testRegisterPermission(): void {
		$sourceType = new TestShareSourceType1([]);
		$this->registry->registerSourceType($sourceType);

		$permissionType1 = new TestSharePermissionType1();
		$this->registry->registerPermissionType($sourceType::class, $permissionType1);
		$permissionType2 = new TestSharePermissionType2();
		$this->registry->registerPermissionType(null, $permissionType2);

		$this->assertEquals([$permissionType1::class => $permissionType1, $permissionType2::class => $permissionType2], $this->registry->getPermissionTypes());
		$this->assertEquals([$sourceType::class => [$permissionType1::class]], $this->registry->getSourceTypePermissionTypeClasses());
		$this->assertEquals([$permissionType2::class], $this->registry->getGenericPermissionTypeClasses());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPermissionType($sourceType::class, $permissionType1);
	}

	public function testRegisterPermissionPreset(): void {
		$permissionPreset = new TestSharePermissionPreset1();
		$this->registry->registerPermissionPreset($permissionPreset);

		$this->assertEquals([$permissionPreset::class => $permissionPreset], $this->registry->getPermissionPresets());

		$this->expectException(RuntimeException::class);
		$this->registry->registerPermissionPreset($permissionPreset);
	}

	public function testMarkPermissionTypeCompatibleWithPermissionPreset(): void {
		$permissionPreset = new TestSharePermissionPreset1();
		$this->registry->registerPermissionPreset($permissionPreset);

		$permissionType = new TestSharePermissionType1();
		$this->registry->registerPermissionType(null, $permissionType);
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset($permissionType::class, $permissionPreset::class);
		$this->registry->markPermissionTypeCompatibleWithPermissionPreset($permissionType::class, $permissionPreset::class);

		$this->assertEquals([$permissionType::class => [$permissionPreset::class]], $this->registry->getPermissionTypeCompatiblePermissionPresetClasses());
		$this->assertEquals([$permissionPreset::class => [$permissionType::class]], $this->registry->getPermissionPresetCompatiblePermissionTypeClasses());

		$this->registry->markPermissionTypeCompatibleWithPermissionPreset($permissionType::class, TestSharePermissionPreset2::class);
		$this->expectException(RuntimeException::class);
		$this->registry->getPermissionTypeCompatiblePermissionPresetClasses();
	}
}
