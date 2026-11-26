<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\User;

use NCU\User\IUserBackend;
use OCP\IDBConnection;
use Override;

abstract class AbstractBackend implements IUserBackend {
	public function __construct(
		protected readonly IDBConnection $connection,
		protected readonly int $id,
		protected readonly string $name,
	) {
	}

	#[Override]
	public function getId(): int {
		return $this->id;
	}

	#[Override]
	public function getName(): string {
		return $this->name;
	}
}
