<?php
/**
 * @copyright Copyright (c) 2020, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
