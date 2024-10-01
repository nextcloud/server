<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;

class TalkContext implements Context {
	/**
	 * @BeforeFeature @Talk
	 * @BeforeScenario @Talk
	 */
	public static function skipTestsIfTalkIsNotInstalled() {
		if (!TalkContext::isTalkInstalled()) {
			throw new Exception('Talk needs to be installed to run features or scenarios tagged with @Talk');
		}
	}

	/**
	 * @AfterScenario @Talk
	 */
	public static function disableTalk() {
		TalkContext::runOcc(['app:disable', 'spreed']);
	}

	private static function isTalkInstalled(): bool {
		$appList = TalkContext::runOcc(['app:list']);

		return strpos($appList, 'spreed') !== false;
	}

	private static function runOcc(array $args): string {
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
		$process = proc_open('php console.php ' . $args, $descriptor, $pipes, $ocPath = '../..');
		$lastStdOut = stream_get_contents($pipes[1]);
		proc_close($process);

		return $lastStdOut;
	}
}
