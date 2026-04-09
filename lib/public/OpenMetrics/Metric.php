<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OpenMetrics;

/**
 * @since 33.0.0
 */
final readonly class Metric {
	public function __construct(
		public int|float|bool|MetricValue $value = false,
		/** @var string[] */
		public array $labels = [],
		public int|float|null $timestamp = null,
	) {
		$this->validateLabels();
	}

	public function label(string $name): ?string {
		return $this->labels[$name] ?? null;
	}

	private function validateLabels(): void {
		foreach ($this->labels as $label => $_value) {
			if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string)$label) !== 1) {
				throw new \InvalidArgumentException('Invalid OpenMetrics label name: "' . $label . '"');
			}
		}
	}
}
