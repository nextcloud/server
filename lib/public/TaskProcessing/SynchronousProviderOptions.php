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

	/**
	 * @param bool $includeWatermarks Whether to include the watermark in the media output files or not
	 * @param bool $preferStreaming Whether to prefer streaming the output or not
	 * @param null|callable $reportOutput Callback for the provider to report the intermediate output (streaming)
	 * @return void
	 * @since 35.0.0
	 */
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

	/**
	 * Get the includeWatermarks option value
	 * @return bool Whether to include the watermark in the media output files or not
	 * @since 35.0.0
	 */
	public function getIncludeWatermarks(): bool {
		return $this->includeWatermarks;
	}

	/**
	 * Get the preferStreaming option value
	 * @return bool Whether to prefer streaming the output or not
	 * @since 35.0.0
	 */
	public function getPreferStreaming(): bool {
		return $this->preferStreaming;
	}

	/**
	 * Get the reportOutput option value
	 * @return callable Callback for the provider to report the intermediate output (streaming)
	 * @since 35.0.0
	 */
	public function getReportOutput(): callable {
		return $this->reportOutput;
	}
}
