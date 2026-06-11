<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use OC\Core\Command\Db\DbSize;
use OC\DB\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class DbSizeTest extends TestCase {

    private Connection&MockObject $connection;
    private InputInterface&MockObject $input;
    private DbSize $command;

    protected function setUp(): void {
        parent::setUp();
        $this->connection = $this->createMock(Connection::class);
        $this->input      = $this->createMock(InputInterface::class);
        $this->command    = new DbSize($this->connection);
    }

    private function mockRows(): array {
        return [
            ['table' => 'oc_filecache', 'total_mb' => 12.50, 'data_mb' => 10.00, 'index_mb' => 2.50, 'rows' => 5000, 'avg_row_bytes' => 2560],
            ['table' => 'oc_share',     'total_mb' =>  3.25, 'data_mb' =>  2.00, 'index_mb' => 1.25, 'rows' =>  200, 'avg_row_bytes' => 16384],
        ];
    }

    private function mockResult(array $rows): Result&MockObject {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);
        return $result;
    }

    public function testMySQLOutputContainsTableAndTotal(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockRows()));
        $this->input->method('getOption')->willReturnMap([['json', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $content = $output->fetch();
        $this->assertStringContainsString('oc_filecache', $content);
        $this->assertStringContainsString('Total database size', $content);
    }

    public function testPostgreSQLOutputContainsTableAndTotal(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(PostgreSQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockRows()));
        $this->input->method('getOption')->willReturnMap([['json', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Total database size', $output->fetch());
    }

    public function testSQLiteReturnsSuccessWithMessage(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(SqlitePlatform::class));
        $this->input->method('getOption')->willReturnMap([['json', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('not supported for SQLite', $output->fetch());
    }

    public function testJsonOutputIsValidArray(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockRows()));
        $this->input->method('getOption')->willReturnMap([['json', true]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $data = json_decode($output->fetch(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('table',    $data[0]);
        $this->assertArrayHasKey('total_mb', $data[0]);
    }

    public function testTotalSizeCalculation(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockRows()));
        $this->input->method('getOption')->willReturnMap([['json', false]]);

        $output = new BufferedOutput();
        self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        // 12.50 + 3.25 = 15.75
        $this->assertStringContainsString('15.75', $output->fetch());
    }
}
