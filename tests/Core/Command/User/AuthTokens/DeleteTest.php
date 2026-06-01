<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\User\AuthTokens;

use OC\Authentication\Token\IProvider;
use OC\Core\Command\User\AuthTokens\Delete;
use OCP\Authentication\Exceptions\WipeTokenException;
use OCP\Authentication\Token\IToken;
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

	#[\Override]
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

	/**
	 * Default option mapping: --last-used-before unset, --cancel-wipe unset.
	 *
	 * @param string|null $lastUsedBefore
	 * @param bool $cancelWipe
	 */
	private function mockOptions(?string $lastUsedBefore = null, bool $cancelWipe = false): void {
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['last-used-before', $lastUsedBefore],
				['cancel-wipe', $cancelWipe],
			]);
	}

	public function testDeleteTokenById(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', '42']
			]);

		$this->mockOptions();

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with('user', 42);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testDeleteTokenByIdRequiresTokenId(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', null]
			]);

		$this->mockOptions();

		$this->expectException(RuntimeException::class);

		$this->tokenProvider->expects($this->never())->method('invalidateTokenById');

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::FAILURE, $result);
	}

	public function testDeleteTokensLastUsedBefore(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', null]
			]);

		$this->mockOptions('946684800');

		$this->tokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testLastUsedBeforeAcceptsIso8601Expanded(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', null]
			]);

		$this->mockOptions('2000-01-01T00:00:00Z');

		$this->tokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testLastUsedBeforeAcceptsYmd(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', null]
			]);

		$this->mockOptions('2000-01-01');

		$this->tokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testIdAndLastUsedBeforeAreMutuallyExclusive(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', '42']
			]);

		$this->mockOptions('946684800');

		$this->expectException(RuntimeException::class);

		$this->tokenProvider->expects($this->never())->method('invalidateLastUsedBefore');

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}

	public function testDeleteByIdRefusesWipePendingWithoutFlag(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', '42']
			]);

		$this->mockOptions();

		$wipeToken = $this->createMock(IToken::class);
		$this->tokenProvider->expects($this->once())
			->method('getTokenById')
			->with(42)
			->willThrowException(new WipeTokenException($wipeToken));

		$this->tokenProvider->expects($this->never())->method('invalidateTokenById');

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains('marked for remote wipe'));

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::FAILURE, $result);
	}

	public function testDeleteByIdAllowsWipePendingWithFlag(): void {
		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['uid', 'user'],
				['id', '42']
			]);

		$this->mockOptions(null, true);

		// With --cancel-wipe, the wipe-state pre-check is skipped entirely
		// (the operator has already opted in), so getTokenById should not run.
		$this->tokenProvider->expects($this->never())->method('getTokenById');

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with('user', 42);

		$result = self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
		$this->assertSame(Command::SUCCESS, $result);
	}
}
