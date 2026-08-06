<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;

class GuestsContext implements Context {
	public const TEST_PASSWORD = '123456';
	protected static $lastStdOut = null;
	protected static $lastCode = null;

	#[\Behat\Hook\BeforeScenario('@Guests')]
	#[\Behat\Hook\BeforeFeature('@Guests')]
	public static function skipTestsIfGuestsIsNotInstalled() {
		if (!self::isGuestsInstalled()) {
			throw new Exception('Guests needs to be installed to run features or scenarios tagged with @Guests');
		}
	}

	#[\Behat\Hook\AfterScenario('@Guests')]
	public static function disableGuests() {
		self::runOcc(['app:disable', 'guests']);
	}

	private static function isGuestsInstalled(): bool {
		self::runOcc(['app:list']);
		return strpos(self::$lastStdOut, 'guests') !== false;
	}

	private static function runOcc(array $args, array $env = []): int {
		// Based on "runOcc" from CommandLine trait (which can not be used due
		// to not being static and being already used in other sibling
		// contexts).
		$args = array_map(function ($arg) {
			return escapeshellarg($arg);
		}, $args);
		$args[] = '--no-ansi --no-warnings';
		$args = implode(' ', $args);

		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open('php console.php ' . $args, $descriptor, $pipes, $ocPath = '../..', $env);
		self::$lastStdOut = stream_get_contents($pipes[1]);
		self::$lastCode = proc_close($process);

		return self::$lastCode;
	}

	#[\Behat\Step\Given('/^user "([^"]*)" is a guest account user$/')]
	public function createGuestUser(string $email): void {
		self::runOcc([
			'user:delete',
			$email,
		]);

		$lastCode = self::runOcc([
			'config:app:set',
			'guests',
			'hash_user_ids',
			'--value=false',
			'--type=boolean',
		]);
		\PHPUnit\Framework\Assert::assertEquals(0, $lastCode);

		$lastCode = self::runOcc([
			'guests:add',
			// creator user
			'admin',
			// email
			$email,
			'--display-name',
			$email . '-displayname',
			'--password-from-env',
		], [
			'OC_PASS' => self::TEST_PASSWORD,
		]);
		\PHPUnit\Framework\Assert::assertEquals(0, $lastCode, 'Guest creation succeeded for ' . $email);
	}

}
