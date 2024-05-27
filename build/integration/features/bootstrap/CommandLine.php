<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

trait CommandLine {
	/** @var int return code of last command */
	private $lastCode;
	/** @var string stdout of last command */
	private $lastStdOut;
	/** @var string stderr of last command */
	private $lastStdErr;

	/** @var string */
	protected $ocPath = '../..';

	/**
	 * Invokes an OCC command
	 *
	 * @param []string $args OCC command, the part behind "occ". For example: "files:transfer-ownership"
	 * @return int exit code
	 */
	public function runOcc($args = []) {
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
		$this->lastStdOut = stream_get_contents($pipes[1]);
		$this->lastStdErr = stream_get_contents($pipes[2]);
		$this->lastCode = proc_close($process);

		// Clean opcode cache
		$client = new GuzzleHttp\Client();
		$client->request('GET', 'http://localhost:8080/apps/testing/clean_opcode_cache.php');

		return $this->lastCode;
	}

	/**
	 * @Given /^invoking occ with "([^"]*)"$/
	 */
	public function invokingTheCommand($cmd) {
		$args = explode(' ', $cmd);
		$this->runOcc($args);
	}

	/**
	 * Find exception texts in stderr
	 */
	public function findExceptions() {
		$exceptions = [];
		$captureNext = false;
		// the exception text usually appears after an "[Exception"] row
		foreach (explode("\n", $this->lastStdErr) as $line) {
			if (preg_match('/\[Exception\]/', $line)) {
				$captureNext = true;
				continue;
			}
			if ($captureNext) {
				$exceptions[] = trim($line);
				$captureNext = false;
			}
		}

		return $exceptions;
	}

	/**
	 * @Then /^the command was successful$/
	 */
	public function theCommandWasSuccessful() {
		$exceptions = $this->findExceptions();
		if ($this->lastCode !== 0) {
			$msg = 'The command was not successful, exit code was ' . $this->lastCode . '.';
			if (!empty($exceptions)) {
				$msg .= ' Exceptions: ' . implode(', ', $exceptions);
			}
			throw new \Exception($msg);
		} elseif (!empty($exceptions)) {
			$msg = 'The command was successful but triggered exceptions: ' . implode(', ', $exceptions);
			throw new \Exception($msg);
		}
	}

	/**
	 * @Then /^the command failed with exit code ([0-9]+)$/
	 */
	public function theCommandFailedWithExitCode($exitCode) {
		if ($this->lastCode !== (int)$exitCode) {
			throw new \Exception('The command was expected to fail with exit code ' . $exitCode . ' but got ' . $this->lastCode);
		}
	}

	/**
	 * @Then /^the command failed with exception text "([^"]*)"$/
	 */
	public function theCommandFailedWithException($exceptionText) {
		$exceptions = $this->findExceptions();
		if (empty($exceptions)) {
			throw new \Exception('The command did not throw any exceptions');
		}

		if (!in_array($exceptionText, $exceptions)) {
			throw new \Exception('The command did not throw any exception with the text "' . $exceptionText . '"');
		}
	}

	/**
	 * @Then /^the command output contains the text "([^"]*)"$/
	 */
	public function theCommandOutputContainsTheText($text) {
		Assert::assertStringContainsString($text, $this->lastStdOut, 'The command did not output the expected text on stdout');
	}

	/**
	 * @Then /^the command error output contains the text "([^"]*)"$/
	 */
	public function theCommandErrorOutputContainsTheText($text) {
		Assert::assertStringContainsString($text, $this->lastStdErr, 'The command did not output the expected text on stderr');
	}
}
