<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\BackgroundJob\Events;

use OCP\BackgroundJob\IJob;
use OCP\EventDispatcher\Event;

/**
 * Emitted before a background job is executed
 *
 * @since 32.0.0
 */
class BeforeJobExecutedEvent extends Event {

	/**
	 * @since 32.0.0
	 */
	public function __construct(
		private IJob $job,
	) {
		parent::__construct();
	}

	/**
	 * @since 32.0.0
	 */
	public function getJob(): IJob {
		return $this->job;
	}

}
