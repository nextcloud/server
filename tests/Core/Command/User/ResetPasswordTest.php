<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Tests\Core\Command\User;

use OC\Core\Command\User\ResetPassword;
use OC\Core\Events\BeforePasswordResetEvent;
use OC\Core\Events\PasswordResetEvent;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ResetPasswordTest extends TestCase {
	#[\PHPUnit\Framework\Attributes\DataProvider('passwordResetResults')]
	public function testPasswordResetEvents(
		bool $passwordChangeSucceeds,
		int $expectedExitCode,
		array $expectedSequence,
		string $expectedOutput,
	): void {
		$sequence = [];

		$user = $this->createMock(IUser::class);
		$user->expects(self::once())
			->method('setPassword')
			->with('NewPassword')
			->willReturnCallback(static function () use (&$sequence, $passwordChangeSucceeds): bool {
				$sequence[] = 'setPassword';
				return $passwordChangeSucceeds;
			});

		$userManager = $this->createMock(IUserManager::class);
		$userManager->expects(self::once())
			->method('get')
			->with('alice')
			->willReturn($user);

		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->expects(self::exactly(count($expectedSequence) - 1))
			->method('dispatchTyped')
			->willReturnCallback(static function (object $event) use (&$sequence, $user): void {
				if ($event instanceof BeforePasswordResetEvent) {
					self::assertSame($user, $event->getUser());
					self::assertSame('NewPassword', $event->getPassword());
					$sequence[] = 'beforeReset';
					return;
				}

				self::assertInstanceOf(PasswordResetEvent::class, $event);
				self::assertSame($user, $event->getUser());
				self::assertSame('NewPassword', $event->getPassword());
				$sequence[] = 'afterReset';
			});

		$input = $this->createMock(InputInterface::class);
		$input->method('getArgument')
			->with('user')
			->willReturn('alice');
		$input->method('getOption')
			->with('password-from-env')
			->willReturn(true);

		$output = $this->createMock(OutputInterface::class);
		$output->expects(self::once())
			->method('writeln')
			->with($expectedOutput);

		$command = new ResetPassword(
			$userManager,
			$this->createStub(IAppManager::class),
			$eventDispatcher,
		);

		$previousPassword = getenv('NC_PASS');
		putenv('NC_PASS=NewPassword');

		try {
			$exitCode = self::invokePrivate($command, 'execute', [$input, $output]);
		} finally {
			if ($previousPassword === false) {
				putenv('NC_PASS');
			} else {
				putenv('NC_PASS=' . $previousPassword);
			}
		}

		self::assertSame($expectedExitCode, $exitCode);
		self::assertSame($expectedSequence, $sequence);
	}

	public static function passwordResetResults(): array {
		return [
			'success dispatches both reset events around the password change' => [
				true,
				0,
				['beforeReset', 'setPassword', 'afterReset'],
				'<info>Successfully reset password for alice</info>',
			],
			'failure does not dispatch the after-reset event' => [
				false,
				1,
				['beforeReset', 'setPassword'],
				'<error>Error while resetting password!</error>',
			],
		];
	}
}
