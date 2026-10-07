<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use OC\DB\Connection;
use OC\DB\SchemaChecker;
use OCP\App\IAppManager;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SchemaCheckerTest extends \Test\TestCase {
	private Connection&MockObject $connection;
	private IAppConfig&MockObject $appConfig;
	private IAppManager&MockObject $appManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private LoggerInterface&MockObject $logger;
	private SchemaChecker $schemaChecker;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(Connection::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->schemaChecker = new SchemaChecker(
			$this->connection,
			$this->appConfig,
			$this->appManager,
			$this->eventDispatcher,
			$this->logger,
		);
	}

	public static function dataFormatFinding(): array {
		return [
			'missing_table' => [['table' => 'oc_foo', 'type' => 'missing_table'], "missing table 'oc_foo'"],
			'unexpected_table' => [['table' => 'oc_foo', 'type' => 'unexpected_table'], "unexpected table 'oc_foo'"],
			'missing_column' => [['table' => 'oc_foo', 'type' => 'missing_column', 'name' => 'bar'], "oc_foo: missing column 'bar'"],
			'unexpected_column' => [['table' => 'oc_foo', 'type' => 'unexpected_column', 'name' => 'bar'], "oc_foo: unexpected column 'bar'"],
			'modified_column' => [['table' => 'oc_foo', 'type' => 'modified_column', 'name' => 'bar', 'changes' => ['type', 'default']], "oc_foo: column 'bar' differs in: type, default"],
			'missing_index' => [['table' => 'oc_foo', 'type' => 'missing_index', 'name' => 'bar_idx'], "oc_foo: missing index 'bar_idx'"],
			'unexpected_index' => [['table' => 'oc_foo', 'type' => 'unexpected_index', 'name' => 'bar_idx'], "oc_foo: unexpected index 'bar_idx'"],
			'unknown' => [['table' => 'oc_foo', 'type' => 'something_else'], "oc_foo: unknown finding 'something_else'"],
		];
	}

	#[DataProvider('dataFormatFinding')]
	public function testFormatFinding(array $finding, string $expected): void {
		$this->assertSame($expected, $this->schemaChecker->formatFinding($finding));
	}

	public function testPartitionFindingsSplitsBlockingAndDisabled(): void {
		$blockingFinding = ['table' => 'oc_foo', 'type' => 'missing_column', 'name' => 'a', 'app' => 'core', 'enabled' => true];
		$disabledAppFinding = ['table' => 'oc_bar', 'type' => 'missing_column', 'name' => 'b', 'app' => 'files', 'enabled' => false];
		$unattributedFinding = ['table' => 'oc_baz', 'type' => 'unexpected_table', 'app' => null, 'enabled' => false];

		$result = $this->schemaChecker->partitionFindings([
			$blockingFinding,
			$disabledAppFinding,
			$unattributedFinding,
		]);

		$this->assertSame([$blockingFinding], $result['blocking']);
		$this->assertSame(['files' => [$disabledAppFinding]], array_intersect_key($result['byDisabledApp'], ['files' => true]));
		$this->assertSame(['(unknown app)' => [$unattributedFinding]], array_intersect_key($result['byDisabledApp'], ['(unknown app)' => true]));
	}

	public function testNormalizeLongStringColumnsRewritesOnlyColumnsOverTheLimit(): void {
		$schema = new Schema();
		$table = $schema->createTable('oc_test');
		$table->addColumn('short_col', Types::STRING, ['length' => 255]);
		$table->addColumn('long_col', Types::STRING, ['length' => 4001]);

		self::invokePrivate($this->schemaChecker, 'normalizeLongStringColumns', [$schema]);

		$this->assertSame(Types::STRING, Type::getTypeRegistry()->lookupName($table->getColumn('short_col')->getType()));
		$this->assertSame(255, $table->getColumn('short_col')->getLength());

		$this->assertInstanceOf(TextType::class, $table->getColumn('long_col')->getType());
		$this->assertNull($table->getColumn('long_col')->getLength());
	}

	public static function dataIsIgnorableTextDefaultDiff(): array {
		return [
			'mysql text' => [IDBConnection::PLATFORM_MYSQL, Types::TEXT, true],
			'mariadb blob' => [IDBConnection::PLATFORM_MARIADB, Types::BLOB, true],
			'mysql string is not ignorable' => [IDBConnection::PLATFORM_MYSQL, Types::STRING, false],
			'sqlite text is not ignorable' => [IDBConnection::PLATFORM_SQLITE, Types::TEXT, false],
		];
	}

	#[DataProvider('dataIsIgnorableTextDefaultDiff')]
	public function testIsIgnorableTextDefaultDiff(string $provider, string $typeName, bool $expected): void {
		$this->connection->method('getDatabaseProvider')->willReturn($provider);

		$column = new Column('some_col', Type::getType($typeName));
		$columnDiff = new ColumnDiff('some_col', $column, ['default'], $column);

		$result = self::invokePrivate($this->schemaChecker, 'isIgnorableTextDefaultDiff', [$columnDiff]);

		$this->assertSame($expected, $result);
	}

	public function testGetOptionalIndexNamesCollectsMissingAndReplacedIndices(): void {
		$this->connection->method('getPrefix')->willReturn('oc_');
		$this->eventDispatcher->method('dispatchTyped')
			->willReturnCallback(function (AddMissingIndicesEvent $event): void {
				$event->addMissingIndex('foo', 'foo_idx', ['col']);
				$event->replaceIndex('bar', ['old_idx'], 'new_idx', ['col'], false);
			});

		$names = self::invokePrivate($this->schemaChecker, 'getOptionalIndexNames');

		$this->assertSame([
			'oc_foo' => ['foo_idx' => true],
			'oc_bar' => ['new_idx' => true, 'old_idx' => true],
		], $names);
	}

	public static function dataIsOptionalIndexFinding(): array {
		$optionalIndexNames = ['oc_foo' => ['foo_idx' => true]];

		return [
			'matching missing_index' => [['table' => 'oc_foo', 'type' => 'missing_index', 'name' => 'foo_idx'], $optionalIndexNames, true],
			'matching unexpected_index' => [['table' => 'oc_foo', 'type' => 'unexpected_index', 'name' => 'foo_idx'], $optionalIndexNames, true],
			'different index name' => [['table' => 'oc_foo', 'type' => 'missing_index', 'name' => 'other_idx'], $optionalIndexNames, false],
			'different table' => [['table' => 'oc_bar', 'type' => 'missing_index', 'name' => 'foo_idx'], $optionalIndexNames, false],
			'non-index finding type' => [['table' => 'oc_foo', 'type' => 'missing_column', 'name' => 'foo_idx'], $optionalIndexNames, false],
		];
	}

	/**
	 * Optional-index findings must be filtered out entirely (never reach
	 * partitionFindings() or --output=json), not just hidden from plain-text
	 * output - Settings already has a dedicated admin-overview surface for them.
	 */
	#[DataProvider('dataIsOptionalIndexFinding')]
	public function testIsOptionalIndexFinding(array $finding, array $optionalIndexNames, bool $expected): void {
		$result = self::invokePrivate($this->schemaChecker, 'isOptionalIndexFinding', [$finding, $optionalIndexNames]);

		$this->assertSame($expected, $result);
	}
}
