<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\WorkflowEngine;

/**
 * @experimental 34.0.0
 */
final readonly class RuntimeScope {

	/**
	 * @experimental 34.0.0
	 *
	 * @param string $operationId
	 * @param int $type
	 * @param string $value
	 */
	public function __construct(
		public string $operationId,
		public int $type,
		public string $value,
	) {
	}

	/**
	 * @experimental 34.0.0
	 * @return array<key-of<properties-of<self>>, value-of<properties-of<self>>>
	 */
	public function toArray(): array {
		return [
			'operationId' => $this->operationId,
			'type' => $this->type,
			'value' => $this->value,
		];
	}
}
