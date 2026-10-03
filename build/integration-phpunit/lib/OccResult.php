<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NextcloudIntegration;

/**
 * Outcome of a single occ invocation.
 */
final readonly class OccResult {
	public function __construct(
		public int $exitCode,
		public string $stdOut,
		public string $stdErr,
	) {
	}

	public function succeeded(): bool {
		return $this->exitCode === 0 && $this->exceptions() === [];
	}

	/**
	 * Exception texts reported on stderr. The message follows the line
	 * containing "[Exception]".
	 *
	 * @return string[]
	 */
	public function exceptions(): array {
		$exceptions = [];
		$captureNext = false;
		foreach (explode("\n", $this->stdErr) as $line) {
			if (str_contains($line, '[Exception]')) {
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

	public function describe(): string {
		return sprintf(
			"exit code %d\n--- stdout ---\n%s\n--- stderr ---\n%s",
			$this->exitCode,
			trim($this->stdOut),
			trim($this->stdErr),
		);
	}
}
