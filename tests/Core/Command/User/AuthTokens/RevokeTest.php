<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\User\AuthTokens;

use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\PublicKeyTokenMapper;
use OC\Core\Command\User\AuthTokens\Revoke;
use OCP\Authentication\Token\IToken;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class RevokeTest extends TestCase {
	protected IUserManager&MockObject $userManager;
	protected IProvider&MockObject $tokenProvider;
	protected PublicKeyTokenMapper&MockObject $mapper;
	protected InputInterface&MockObject $input;
	protected OutputInterface&MockObject $output;
	protected Revoke $command;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->mapper = $this->createMock(PublicKeyTokenMapper::class);
		$this->input = $this->createMock(InputInterface::class);
		$this->output = $this->createMock(OutputInterface::class);

		$this->command = new Revoke(
			$this->userManager,
			$this->tokenProvider,
			$this->mapper,
		);
	}

	public function testExecuteFailsWithoutMode(): void {
		$this->input->method('getArgument')
			->with('uid')
			->willReturn('alice');

		$this->input->method('getOption')
			->willReturnCallback(function (string $option) {
				return match ($option) {
					'all-users',
					'sessions',
					'remembered-sessions',
					'all-except-app-passwords',
					'all',
					'dry-run',
					'force' => false,
					default => throw new \InvalidArgumentException("Unexpected option $option"),
				};
			});

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Specify exactly one of --sessions, --remembered-sessions, --all-except-app-passwords, or --all.');

		self::invokePrivate($this->command, 'execute', [$this->input, $this->output]);
	}

	public function testSessionsRevokesOnlyTemporaryTokens(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$tempToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 101,
			'getType' => IToken::TEMPORARY_TOKEN,
			'getRemember' => IToken::DO_NOT_REMEMBER,
			'getName' => 'Firefox',
		]);

		$rememberedTempToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 102,
			'getType' => IToken::TEMPORARY_TOKEN,
			'getRemember' => IToken::REMEMBER,
			'getName' => 'Remembered browser',
		]);

		$permanentToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 201,
			'getType' => IToken::PERMANENT_TOKEN,
			'getRemember' => IToken::DO_NOT_REMEMBER,
			'getName' => 'Desktop client',
		]);

		$this->input->method('getArgument')
			->with('uid')
			->willReturn('alice');

		$this->input->method('getOption')
			->willReturnCallback(function (string $option) {
				return match ($option) {
					'all-users' => false,
					'sessions' => true,
					'remembered-sessions' => false,
					'all-except-app-passwords' => false,
					'all' => false,
					'dry-run' => false,
					'force' => true,
					default => throw new \InvalidArgumentException("Unexpected option $option"),
				};
			});

		$this->userManager->expects($this->once())
			->method('get')
			->with('alice')
			->willReturn($user);

		$this->tokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with('alice')
			->willReturn([$tempToken, $rememberedTempToken, $permanentToken]);

		$this->tokenProvider->expects($this->exactly(2))
			->method('invalidateTokenById')
			->willReturnCallback(function (string $uid, int $id): void {
				self::assertSame('alice', $uid);
				self::assertContains($id, [101, 102]);
			});

		$this->output->method('isVerbose')->willReturn(false);
		$this->output->expects($this->once())
			->method('writeln')
			->with('<info>Revoked 2 token(s).</info>');

		$result = self::invokePrivate($this->command, 'execute', [$this->input, $this->output]);

		self::assertSame(0, $result);
	}

	public function testAllExceptAppPasswordsDryRunDoesNotRevoke(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$tempToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 101,
			'getType' => IToken::TEMPORARY_TOKEN,
			'getRemember' => IToken::DO_NOT_REMEMBER,
			'getName' => 'Firefox',
		]);

		$wipeToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 301,
			'getType' => IToken::WIPE_TOKEN,
			'getRemember' => IToken::DO_NOT_REMEMBER,
			'getName' => 'Remote wipe',
		]);

		$permanentToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 201,
			'getType' => IToken::PERMANENT_TOKEN,
			'getRemember' => IToken::DO_NOT_REMEMBER,
			'getName' => 'Desktop client',
		]);

		$this->input->method('getArgument')
			->with('uid')
			->willReturn('alice');

		$this->input->method('getOption')
			->willReturnCallback(function (string $option) {
				return match ($option) {
					'all-users' => false,
					'sessions' => false,
					'remembered-sessions' => false,
					'all-except-app-passwords' => true,
					'all' => false,
					'dry-run' => true,
					'force' => true,
					default => throw new \InvalidArgumentException("Unexpected option $option"),
				};
			});

		$this->userManager->expects($this->once())
			->method('get')
			->with('alice')
			->willReturn($user);

		$this->tokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with('alice')
			->willReturn([$tempToken, $wipeToken, $permanentToken]);

		$this->tokenProvider->expects($this->never())
			->method('invalidateTokenById');

		$this->output->method('isVerbose')->willReturn(false);
		$this->output->expects($this->once())
			->method('writeln')
			->with('<info>Dry run complete. 2 token(s) would be revoked.</info>');

		$result = self::invokePrivate($this->command, 'execute', [$this->input, $this->output]);

		self::assertSame(0, $result);
	}

	public function testAllUsersBulkRevokeSessionsUsesBulkPath(): void {
		$this->input->method('getArgument')
			->with('uid')
			->willReturn(null);

		$this->input->method('getOption')
			->willReturnCallback(function (string $option) {
				return match ($option) {
					'all-users' => true,
					'sessions' => true,
					'remembered-sessions' => false,
					'all-except-app-passwords' => false,
					'all' => false,
					'dry-run' => false,
					'force' => true,
					default => throw new \InvalidArgumentException("Unexpected option $option"),
				};
			});

		// Bulk path should call the mapper directly, not iterate users
		$this->mapper->expects($this->once())
			->method('invalidateByType')
			->with(IToken::TEMPORARY_TOKEN)
			->willReturn(5);

		$this->userManager->expects($this->never())
			->method('callForAllUsers');

		$this->tokenProvider->expects($this->never())
			->method('getTokenByUser');

		$this->output->method('isVerbose')->willReturn(false);
		$this->output->expects($this->once())
			->method('writeln')
			->with('<info>Revoked 5 token(s).</info>');

		$result = self::invokePrivate($this->command, 'execute', [$this->input, $this->output]);

		self::assertSame(0, $result);
	}

	public function testAllUsersDryRunFallsBackToPerUserIteration(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$tempToken = $this->createConfiguredMock(IToken::class, [
			'getId' => 101,
			'getType' => IToken::TEMPORARY_TOKEN,
			'getRemember' => IToken::DO_NOT_REMEMBER,
			'getName' => 'Firefox',
		]);

		$this->input->method('getArgument')
			->with('uid')
			->willReturn(null);

		$this->input->method('getOption')
			->willReturnCallback(function (string $option) {
				return match ($option) {
					'all-users' => true,
					'sessions' => true,
					'remembered-sessions' => false,
					'all-except-app-passwords' => false,
					'all' => false,
					'dry-run' => true,
					'force' => true,
					default => throw new \InvalidArgumentException("Unexpected option $option"),
				};
			});

		// Bulk mapper methods should NOT be called in dry-run
		$this->mapper->expects($this->never())
			->method('invalidateByType');

		// Should fall back to per-user iteration
		$this->userManager->expects($this->once())
			->method('callForAllUsers')
			->willReturnCallback(function (\Closure $callback) use ($user): void {
				$callback($user);
			});

		$this->tokenProvider->expects($this->once())
			->method('getTokenByUser')
			->with('alice')
			->willReturn([$tempToken]);

		$this->tokenProvider->expects($this->never())
			->method('invalidateTokenById');

		$this->output->method('isVerbose')->willReturn(false);
		$this->output->expects($this->once())
			->method('writeln')
			->with('<info>Dry run complete. 1 token(s) would be revoked.</info>');

		$result = self::invokePrivate($this->command, 'execute', [$this->input, $this->output]);

		self::assertSame(0, $result);
	}
}
