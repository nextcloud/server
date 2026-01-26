<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OC\SystemConfig;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use OCP\ServerVersion;
use Override;

/**
 * Export some basic information about current instance
 */
class InstanceInfo implements IMetricFamily {
	public function __construct(
		private SystemConfig $systemConfig,
		private ServerVersion $serverVersion,
	) {
	}

	#[Override]
	public function name(): string {
		return 'instance_info';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::info;
	}

	#[Override]
	public function unit(): string {
		return '';
	}

	#[Override]
	public function help(): string {
		return 'Basic information about Nextcloud';
	}

	#[Override]
	public function metrics(): Generator {
		yield new Metric(
			1,
			[
				'full_version' => $this->serverVersion->getHumanVersion(),
				'major_version' => (string)$this->serverVersion->getVersion()[0],
				'build' => $this->serverVersion->getBuild(),
				'installed' => $this->systemConfig->getValue('installed', false) ? '1' : '0',
			],
			time()
		);
	}
}
