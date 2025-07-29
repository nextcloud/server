<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TextProcessing\Events;

use OCP\EventDispatcher\Event;
use OCP\TextProcessing\Task;

/**
 * @since 27.1.0
 * @deprecated 30.0.0
 */
abstract class AbstractTextProcessingEvent extends Event {
	/**
	 * @since 27.1.0
	 */
	public function __construct(
		private Task $task,
	) {
		parent::__construct();
	}

	/**
	 * @return Task
	 * @since 27.1.0
	 */
	public function getTask(): Task {
		return $this->task;
	}
}
