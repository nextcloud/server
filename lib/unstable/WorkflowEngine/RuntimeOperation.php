<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\WorkflowEngine;

use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IOperation;

/**
 * @experimental 34.0.0
 */
final readonly class RuntimeOperation {

	private bool $runtime;

	/**
	 * @experimental 34.0.0
	 *
	 * @param string $id
	 * @param class-string<IOperation> $class
	 * @param string $name
	 * @param list<string> $checks
	 * @param string $operation
	 * @param class-string<IEntity> $entity
	 * @param list<string> $events
	 * @param string $appId
	 */
	public function __construct(
		public string $id,
		public string $class,
		public string $name,
		public array $checks,
		public string $operation,
		public string $entity,
		public array $events,
		public string $appId,
	) {
		$this->runtime = true;
	}

	/**
	 * @experimental 34.0.0
	 * @return array<key-of<properties-of<self>>, value-of<properties-of<self>>>
	 */
	public function toArray(): array {
		return [
			'id' => $this->id,
			'class' => $this->class,
			'name' => $this->name,
			'checks' => $this->checks,
			'operation' => $this->operation,
			'entity' => $this->entity,
			'events' => $this->events,
			'appId' => $this->appId,
			'runtime' => $this->runtime,
		];
	}
}
