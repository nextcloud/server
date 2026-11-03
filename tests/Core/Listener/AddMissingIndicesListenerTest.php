<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Josh Duffy <joshduff.jd@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Listener;

use OC\Core\Listener\AddMissingIndicesListener;
use OC\SystemConfig;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\DB\IResult;
use OCP\IDBConnection;
use Test\TestCase;

class AddMissingIndicesListenerTest extends TestCase {
	public function testRegistersBoundedFilesSearchIndicesForEnabledMariaDb1011(): void {
		$connection = $this->createMock(IDBConnection::class);
		$connection->method('getDatabaseProvider')->willReturn(IDBConnection::PLATFORM_MARIADB);
		$result = $this->createMock(IResult::class);
		$result->method('fetchOne')->willReturn('10.11.14-MariaDB');
		$result->expects($this->once())->method('closeCursor')->willReturn(true);
		$connection->expects($this->once())->method('executeQuery')->with('SELECT VERSION()')->willReturn($result);

		$config = $this->createMock(SystemConfig::class);
		$config->expects($this->once())->method('getValue')->with('files_search_bounded_name', false)->willReturn(true);

		$event = new AddMissingIndicesEvent();
		(new AddMissingIndicesListener($connection, $config))->handle($event);

		$indices = array_column($event->getMissingIndices(), null, 'indexName');
		$this->assertSame([
			'tableName' => 'filecache',
			'indexName' => 'fs_storage_name_mtime_fileid',
			'columns' => ['storage', 'name', 'mtime', 'fileid'],
			'options' => [],
			'dropUnnamedIndex' => false,
			'uniqueIndex' => false,
		], $indices['fs_storage_name_mtime_fileid']);
		$this->assertSame([
			'tableName' => 'filecache',
			'indexName' => 'fs_storage_mtime_fileid_name',
			'columns' => ['storage', 'mtime', 'fileid', 'name'],
			'options' => [],
			'dropUnnamedIndex' => false,
			'uniqueIndex' => false,
		], $indices['fs_storage_mtime_fileid_name']);
	}

	public function testDoesNotRegisterBoundedFilesSearchIndicesWhenFeatureIsDisabled(): void {
		$connection = $this->createMock(IDBConnection::class);
		$connection->method('getDatabaseProvider')->willReturn(IDBConnection::PLATFORM_MARIADB);
		$connection->expects($this->never())->method('executeQuery');
		$config = $this->createMock(SystemConfig::class);
		$config->expects($this->once())->method('getValue')->with('files_search_bounded_name', false)->willReturn(false);

		$event = new AddMissingIndicesEvent();
		(new AddMissingIndicesListener($connection, $config))->handle($event);

		$names = array_column($event->getMissingIndices(), 'indexName');
		$this->assertNotContains('fs_storage_name_mtime_fileid', $names);
		$this->assertNotContains('fs_storage_mtime_fileid_name', $names);
	}

	public function testDoesNotRegisterBoundedFilesSearchIndicesForOtherMariaDbVersions(): void {
		$connection = $this->createMock(IDBConnection::class);
		$connection->method('getDatabaseProvider')->willReturn(IDBConnection::PLATFORM_MARIADB);
		$result = $this->createMock(IResult::class);
		$result->method('fetchOne')->willReturn('10.6.23-MariaDB');
		$result->expects($this->once())->method('closeCursor')->willReturn(true);
		$connection->expects($this->once())->method('executeQuery')->with('SELECT VERSION()')->willReturn($result);
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')->with('files_search_bounded_name', false)->willReturn(true);

		$event = new AddMissingIndicesEvent();
		(new AddMissingIndicesListener($connection, $config))->handle($event);

		$names = array_column($event->getMissingIndices(), 'indexName');
		$this->assertNotContains('fs_storage_name_mtime_fileid', $names);
		$this->assertNotContains('fs_storage_mtime_fileid_name', $names);
	}
}
