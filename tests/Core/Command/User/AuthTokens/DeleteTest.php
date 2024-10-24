<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Tests\Core\Command\User\AuthTokens;

use OC\Authentication\Token\IProvider;
use OC\Core\Command\User\AuthTokens\Delete;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DeleteTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $tokenProvider;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$tokenProvider = $this->tokenProvider = $this->getMockBuilder(IProvider::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var \OC\Authentication\Token\IProvider $tokenProvider */
		$this->command = new Delete($tokenProvider);
	}

	public function testDeleteTokenById(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->withConsecutive(['uid'], ['id'])
			->willReturnOnConsecutiveCalls('user', 42);

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('last-used-before')
			->willReturn(null);

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with('user', 42);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testDeleteTokenByIdRequiresTokenId(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->withConsecutive(['uid'], ['id'])
			->willReturnOnConsecutiveCalls('user', null);

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('last-used-before')
			->willReturn(null);

		$this->expectException(RuntimeException::class);

		$this->tokenProvider->expects($this->never())->method('invalidateTokenById');

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::FAILURE, $result);
	}

	public function testDeleteTokensLastUsedBefore(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->withConsecutive(['uid'], ['id'])
			->willReturnOnConsecutiveCalls('user', null);

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('last-used-before')
			->willReturn('946684800');

		$this->tokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testLastUsedBeforeAcceptsIso8601Expanded(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->withConsecutive(['uid'], ['id'])
			->willReturnOnConsecutiveCalls('user', null);

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('last-used-before')
			->willReturn('2000-01-01T00:00:00Z');

		$this->tokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testLastUsedBeforeAcceptsYmd(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->withConsecutive(['uid'], ['id'])
			->willReturnOnConsecutiveCalls('user', null);

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('last-used-before')
			->willReturn('2000-01-01');

		$this->tokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testIdAndLastUsedBeforeAreMutuallyExclusive(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->withConsecutive(['uid'], ['id'])
			->willReturnOnConsecutiveCalls('user', 42);

		$this->consoleInput->expects($this->once())
			->method('getOption')
			->with('last-used-before')
			->willReturn('946684800');

		$this->expectException(RuntimeException::class);

		$this->tokenProvider->expects($this->never())->method('invalidateLastUsedBefore');

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}
}
