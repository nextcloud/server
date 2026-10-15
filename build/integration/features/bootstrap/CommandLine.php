<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use PHPUnit\Framework\Assert;

require __DIR__ . '/autoload.php';

trait CommandLine {
	use OccRunner;

	/**
	 * @Given /^invoking occ with "([^"]*)"$/
	 */
	public function invokingTheCommand(string $cmd): void {
		$args = explode(' ', $cmd);
		$this->runOcc($args);
	}

	/**
	 * @Given /^invoking occ with "([^"]*)" with input "([^"]+)"$/
	 */
	public function invokingTheCommandWith(string $cmd, string $inputString): void {
		$args = explode(' ', $cmd);
		$this->runOcc($args, $inputString);
	}

	/**
	 * Find exception texts in stderr
	 */
	public function findExceptions(): array {
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
	public function theCommandWasSuccessful(): void {
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
	public function theCommandFailedWithExitCode($exitCode): void {
		if ($this->lastCode !== (int)$exitCode) {
			throw new \Exception('The command was expected to fail with exit code ' . $exitCode . ' but got ' . $this->lastCode);
		}
	}

	/**
	 * @Then /^the command failed with exception text "([^"]*)"$/
	 */
	public function theCommandFailedWithException(string $exceptionText): void {
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
	public function theCommandOutputContainsTheText(string $text): void {
		Assert::assertStringContainsString($text, $this->lastStdOut, 'The command did not output the expected text on stdout.');
	}

	/**
	 * @Then /^the command output does not contain the text "([^"]*)"$/
	 */
	public function theCommandOutputDoesNotContainTheText(string $text): void {
		Assert::assertStringNotContainsString($text, $this->lastStdOut, 'The command did output the not expected text on stdout.');
	}

	/**
	 * @Then /^the command error output contains the text "([^"]*)"$/
	 */
	public function theCommandErrorOutputContainsTheText(string $text): void {
		Assert::assertStringContainsString($text, $this->lastStdErr, 'The command did not output the expected text on stderr.');
	}
}
