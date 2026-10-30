<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\IConfig;
use OCP\ILogger;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;

class LogLevel implements IMetricFamily {
	public function __construct(
		private IConfig $config,
	) {
	}

	#[\Override]
	public function name(): string {
		return 'log_level';
	}

	#[\Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[\Override]
	public function unit(): string {
		return '';
	}

	#[\Override]
	public function help(): string {
		return 'Current log level (lower level means more logs)';
	}

	#[\Override]
	public function metrics(): Generator {
		yield new Metric((int)$this->config->getSystemValue('loglevel', ILogger::WARN));
	}
}
