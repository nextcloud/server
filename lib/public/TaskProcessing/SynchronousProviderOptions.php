<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TaskProcessing;

/**
 * @since 35.0.0
 */
class SynchronousProviderOptions {
	private \Closure $reportOutput;

	public function __construct(
		private readonly bool $includeWatermarks = false,
		private readonly bool $preferStreaming = true,
		?callable $reportOutput = null,
	) {
		$this->reportOutput = $reportOutput !== null
			? \Closure::fromCallable($reportOutput)
			: static function (array $output): bool {
				return true;
			};
	}

	public function getIncludeWatermarks(): bool {
		return $this->includeWatermarks;
	}

	public function getPreferStreaming(): bool {
		return $this->preferStreaming;
	}

	public function getReportOutput(): callable {
		return $this->reportOutput;
	}
}
