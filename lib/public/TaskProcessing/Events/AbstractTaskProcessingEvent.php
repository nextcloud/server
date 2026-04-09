<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TaskProcessing\Events;

use OCP\EventDispatcher\Event;
use OCP\TaskProcessing\Task;

/**
 * @since 30.0.0
 */
abstract class AbstractTaskProcessingEvent extends Event {
	/**
	 * @since 30.0.0
	 */
	public function __construct(
		private readonly Task $task,
	) {
		parent::__construct();
	}

	/**
	 * @return Task
	 * @since 30.0.0
	 */
	public function getTask(): Task {
		return $this->task;
	}
}
