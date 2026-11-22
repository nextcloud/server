<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require __DIR__ . '/autoload.php';

/**
 * Plumbing for running occ commands in behat tests, which does not include
 * the behat step definitions for command line tests.
 */
trait OccRunner {
	/** @var int return code of last command */
	private int $lastCode = 0;
	/** @var string stdout of last command */
	private string $lastStdOut = '';
	/** @var string stderr of last command */
	private string $lastStdErr = '';
	protected string $ocPath = '../..';

	/**
	 * Invokes an OCC command
	 *
	 * @param string[] $args OCC command, the part behind "occ". For example: "files:transfer-ownership"
	 * @return int exit code
	 */
	public function runOcc(array $args = [], string $inputString = ''): int {
		$args = array_map(function ($arg) {
			return escapeshellarg($arg);
		}, $args);
		$args[] = '--no-ansi';
		$args = implode(' ', $args);

		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open('php console.php ' . $args, $descriptor, $pipes, $this->ocPath);
		if ($inputString !== '') {
			fwrite($pipes[0], $inputString . "\n");
			fclose($pipes[0]);
		}
		$this->lastStdOut = stream_get_contents($pipes[1]);
		$this->lastStdErr = stream_get_contents($pipes[2]);
		$this->lastCode = proc_close($process);

		// Clean opcode cache
		$client = new GuzzleHttp\Client();
		$client->request('GET', 'http://localhost:8080/apps/testing/clean_opcode_cache.php');

		return $this->lastCode;
	}
}
