<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Sharing;

use OC\Sharing\ClassMapper;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class ClassMapperTest extends TestCase {
	private IDBConnection $connection;

	private ClassMapper $classMapper;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->clearMappings();
		$this->classMapper = $this->getMapper();
	}

	private function clearMappings(): void {
		$query = $this->connection->getTypedQueryBuilder();
		$query->delete('sharing_classmap');
		$query->executeStatement();
	}

	private function getMapper(): ClassMapper {
		return new ClassMapper($this->connection);
	}

	public function testGetInsert(): void {
		$id = $this->classMapper->getClassId(IDBConnection::class);
		$this->assertEquals($id, $this->classMapper->getClassId(IDBConnection::class));
		$this->assertEquals(IDBConnection::class, $this->classMapper->getClassName($id));

		$this->assertEquals($id, $this->getMapper()->getClassId(IDBConnection::class));
	}

	public function testInsertConcurrent(): void {
		$concurrentMapper = $this->getMapper();

		// trigger a load
		$this->classMapper->getClassId(ClassMapper::class);

		$id = $concurrentMapper->getClassId(IDBConnection::class);
		$this->assertEquals($id, $this->classMapper->getClassId(IDBConnection::class));
		$this->assertEquals(IDBConnection::class, $this->classMapper->getClassName($id));
	}

	public function testGetUnknownId(): void {
		$this->expectException(\Exception::class);
		$this->classMapper->getClassName(1);
	}
}
