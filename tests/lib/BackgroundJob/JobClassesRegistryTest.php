<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use InvalidArgumentException;
use OC\BackgroundJob\JobClassesRegistry;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;
use Test\TestCase;

/**
 * @package Test\BackgroundJob
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class JobClassesRegistryTest extends TestCase {
	private readonly IDBConnection $connection;
	private readonly ISnowflakeGenerator $snowflakeGenerator;
	private JobClassesRegistry $registry;

	#[Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->snowflakeGenerator = Server::get(ISnowflakeGenerator::class);
		$this->registry = new JobClassesRegistry($this->connection, $this->snowflakeGenerator);
	}

	public function testResolveNonExistingClass() {
		$className = 'invalid_class_name_122278';

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Class ' . $className . ' doesn’t exists');
		$this->registry->getId($className);
	}

	public function testResolveInvalidClass() {
		$className = self::class;

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Class ' . $className . ' isn’t an instance of OCP\BackgroundJob\IJob');
		$this->registry->getId($className);
	}

	public function testResolveValidClass() {
		$className = DummyJob::class;

		$classId = $this->registry->getId($className);
		$this->assertIsString($classId);
		$this->assertGreaterThan(0, $classId);

		// Renew register. ID should stay the same
		$this->registry = new JobClassesRegistry($this->connection, $this->snowflakeGenerator);
		$newId = $this->registry->getId($className);
		$this->assertEquals($classId, $newId);
	}

	public function testResolveValidId() {
		$className = DummyJob::class;

		$classId = $this->registry->getId($className);
		$resolvedClass = $this->registry->getName($classId);

		$this->assertEquals($className, $resolvedClass);
	}

	public function testResolveInvalidId() {
		$classId = PHP_INT_MAX;
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Class ID ' . $classId . ' doesn’t match any class name');
		$this->registry->getName($classId);
	}
}
