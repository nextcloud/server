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

	public function name(): string {
		return 'log_level';
	}

	public function type(): MetricType {
		return MetricType::gauge;
	}

	public function unit(): string {
		return '';
	}

	public function help(): string {
		return 'Current log level (lower level means more logs)';
	}

	public function metrics(): Generator {
		yield new Metric((int)$this->config->getSystemValue('loglevel', ILogger::WARN));
	}
}
