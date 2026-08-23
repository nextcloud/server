<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Preview\Failure\PreviewFailureService;
use OC\Preview\PreviewService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

/**
 * @psalm-api - we cannot use final as this will break unit tests
 */
class ExpirePreviewsJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly IConfig $config,
		private readonly PreviewService $service,
		private readonly PreviewFailureService $failureService,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		$this->setInterval(60 * 60 * 24);
	}

	#[\Override]
	protected function run(mixed $argument): void {
		$days = $this->config->getSystemValueInt('preview_expiration_days');
		if ($days > 0) {
			$this->service->deleteExpiredPreviews($days);
		}

		$this->failureService->cleanup();
	}
}
