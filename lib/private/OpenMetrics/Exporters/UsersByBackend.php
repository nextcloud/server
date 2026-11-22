<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OpenMetrics\Exporters;

use Generator;
use OCP\IUserManager;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use Override;

/**
 * Count users of each backend which supports it (mapped users only)
 */
class UsersByBackend implements IMetricFamily {
	public function __construct(
		private IUserManager $userManager,
	) {
	}

	#[Override]
	public function name(): string {
		return 'users';
	}

	#[Override]
	public function type(): MetricType {
		return MetricType::gauge;
	}

	#[Override]
	public function unit(): string {
		return 'users';
	}

	#[Override]
	public function help(): string {
		return 'Number of users by backend';
	}

	#[Override]
	public function metrics(): Generator {
		$userCounts = $this->userManager->countUsers(true);
		foreach ($userCounts as $backend => $count) {
			yield new Metric($count, ['backend' => $backend]);
		}
	}
}
