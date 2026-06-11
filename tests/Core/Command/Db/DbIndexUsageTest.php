<?php

declare(strict_types=1);

namespace Tests\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use OC\Core\Command\Db\DbIndexUsage;
use OC\DB\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class DbIndexUsageTest extends TestCase {

    private Connection&MockObject $connection;
    private InputInterface&MockObject $input;
    private DbIndexUsage $command;

    protected function setUp(): void {
        parent::setUp();
        $this->connection = $this->createMock(Connection::class);
        $this->input      = $this->createMock(InputInterface::class);
        $this->command    = new DbIndexUsage($this->connection);
    }

    private function mockMySQLRows(): array {
        return [
            ['table' => 'oc_filecache', 'index' => 'idx_fc_name', 'reads' => 0, 'writes' => 150],
            ['table' => 'oc_share',     'index' => 'idx_sh_par',  'reads' => 0, 'writes' =>  42],
        ];
    }

    private function mockPostgreSQLRows(): array {
        return [
            ['table' => 'oc_filecache', 'index' => 'idx_fc_name', 'reads' => 0, 'tuples_read' => 0, 'tuples_fetched' => 0],
        ];
    }

    private function mockResult(array $rows): Result&MockObject {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);
        return $result;
    }

    public function testNoUnusedIndexesPrintsSuccessMessage(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult([]));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No unused indexes found', $output->fetch());
    }

    public function testMySQLUnusedIndexesRendersTable(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockMySQLRows()));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $content = $output->fetch();
        $this->assertStringContainsString('Reads',       $content);
        $this->assertStringContainsString('Writes',      $content);
        $this->assertStringContainsString('idx_fc_name', $content);
        $this->assertStringContainsString('Found 2 unused index(es)', $content);
    }

    public function testPostgreSQLUnusedIndexesRendersTable(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(PostgreSQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockPostgreSQLRows()));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $content = $output->fetch();
        $this->assertStringContainsString('Tuples Read',    $content);
        $this->assertStringContainsString('Tuples Fetched', $content);
    }

    public function testAllFlagSuppressesCountMessage(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockMySQLRows()));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', true]]);

        $output = new BufferedOutput();
        self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertStringNotContainsString('Found', $output->fetch());
    }

    public function testDefaultFilterIncludedInQuery(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->stringContains('count_read = 0'))
            ->willReturn($this->mockResult([]));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', false]]);

        self::invokePrivate($this->command, 'execute', [$this->input, new BufferedOutput()]);
    }

    public function testAllFlagRemovesFilterFromQuery(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->logicalNot($this->stringContains('count_read = 0')))
            ->willReturn($this->mockResult([]));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', true]]);

        self::invokePrivate($this->command, 'execute', [$this->input, new BufferedOutput()]);
    }

    public function testJsonOutputWhenRowsExist(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySQLPlatform::class));
        $this->connection->method('executeQuery')
            ->willReturn($this->mockResult($this->mockMySQLRows()));
        $this->input->method('getOption')->willReturnMap([['json', true], ['all', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $data = json_decode($output->fetch(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('table', $data[0]);
        $this->assertArrayHasKey('index', $data[0]);
    }

    public function testSQLiteReturnsSuccessWithMessage(): void {
        $this->connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(SqlitePlatform::class));
        $this->input->method('getOption')->willReturnMap([['json', false], ['all', false]]);

        $output = new BufferedOutput();
        $exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('not supported for SQLite', $output->fetch());
    }
}
