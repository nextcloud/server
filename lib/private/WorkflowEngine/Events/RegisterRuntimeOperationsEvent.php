<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\WorkflowEngine\Events;

use OCP\EventDispatcher\Event;
use OCP\WorkflowEngine\IManager;

/**
 * @since 34.0.0
 */
class RegisterRuntimeOperationsEvent extends Event {

	public function __construct(
		private readonly IManager $manager,
	) {
		parent::__construct();
	}

	public function getManager(): IManager {
		return $this->manager;
	}
}
