<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TaskProcessing\Events;

use OCP\EventDispatcher\Event;
use OCP\TaskProcessing\IProvider;
use OCP\TaskProcessing\ITaskType;

/**
 * Event dispatched by the server to collect Task Processing Providers
 * and custom Task Types from listeners (like AppAPI).
 *
 * Listeners should add their providers and task types using the
 * addProvider() and addTaskType() methods.
 *
 * @since 32.0.0
 */
class GetTaskProcessingProvidersEvent extends Event {
	/** @var IProvider[] */
	private array $providers = [];

	/** @var ITaskType[] */
	private array $taskTypes = [];

	/**
	 * Add a Task Processing Provider.
	 *
	 * @param IProvider $provider The provider instance to add.
	 * @since 32.0.0
	 */
	public function addProvider(IProvider $provider): void {
		$this->providers[] = $provider;
	}

	/**
	 * Get all collected Task Processing Providers.
	 *
	 * @return IProvider[]
	 * @since 32.0.0
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * Add a custom Task Processing Task Type.
	 *
	 * @param ITaskType $taskType The task type instance to add.
	 * @since 32.0.0
	 */
	public function addTaskType(ITaskType $taskType): void {
		$this->taskTypes[] = $taskType;
	}

	/**
	 * Get all collected custom Task Processing Task Types.
	 *
	 * @return ITaskType[]
	 * @since 32.0.0
	 */
	public function getTaskTypes(): array {
		return $this->taskTypes;
	}
}
