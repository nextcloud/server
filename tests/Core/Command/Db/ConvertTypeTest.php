<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db;

use OC\Core\Command\Db\ConvertType;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\DB\ConnectionFactory;
use OC\DB\MySqlTools;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class ConvertTypeTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private MySqlTools&MockObject $mySqlTools;
	private QuestionHelper&MockObject $questionHelper;
	private InputInterface&MockObject $input;
	private Connection&MockObject $toDB;
	private BufferedOutput $output;
	private ConvertType $command;

	protected function setUp(): void {
		parent::setUp();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->mySqlTools = $this->createMock(MySqlTools::class);
		$this->questionHelper = $this->createMock(QuestionHelper::class);
		$this->input = $this->createMock(InputInterface::class);
		$this->toDB = $this->createMock(Connection::class);
		$this->output = new BufferedOutput();

		$this->command = new ConvertType(
			$this->createMock(IConfig::class),
			$this->createMock(ConnectionFactory::class),
			$this->createMock(IAppManager::class),
			$this->urlGenerator,
			$this->mySqlTools,
		);
		$this->command->setHelperSet(new HelperSet(['question' => $this->questionHelper]));
	}

	private function checkTarget(): bool {
		return self::invokePrivate($this->command, 'checkTargetSupports4Byte', [$this->toDB, $this->input, $this->output]);
	}

	public function testNonMysqlTargetIsNotChecked(): void {
		$this->input->method('getArgument')->with('type')->willReturn('pgsql');
		$this->mySqlTools->expects($this->never())->method('supports4ByteCharset');
		$this->questionHelper->expects($this->never())->method('ask');

		$this->assertTrue($this->checkTarget());
		$this->assertSame('', $this->output->fetch());
	}

	public function testMysqlTargetWith4ByteSupport(): void {
		$this->input->method('getArgument')->with('type')->willReturn('mysql');
		$this->mySqlTools->expects($this->once())
			->method('supports4ByteCharset')
			->with($this->isInstanceOf(ConnectionAdapter::class))
			->willReturn(true);
		$this->questionHelper->expects($this->never())->method('ask');

		$this->assertTrue($this->checkTarget());
		$this->assertSame('', $this->output->fetch());
	}

	public static function dataMysqlTargetWithout4ByteSupport(): array {
		return [
			'user aborts' => [false],
			'user continues' => [true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataMysqlTargetWithout4ByteSupport')]
	public function testMysqlTargetWithout4ByteSupport(bool $answer): void {
		$this->input->method('getArgument')->with('type')->willReturn('mysql');
		$this->mySqlTools->method('supports4ByteCharset')->willReturn(false);
		$this->urlGenerator->method('linkToDocs')
			->with('admin-mysql-utf8mb4')
			->willReturn('https://docs.example.com/mysql_4byte_support');
		$this->questionHelper->expects($this->once())
			->method('ask')
			->willReturn($answer);

		$this->assertSame($answer, $this->checkTarget());
		$display = $this->output->fetch();
		$this->assertStringContainsString('does not support 4-byte characters', $display);
		$this->assertStringContainsString('https://docs.example.com/mysql_4byte_support', $display);
	}
}
