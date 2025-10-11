<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Uuid;

use OCP\Uuid\IUuid;
use Override;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

class Uuid implements IUuid {
	public function __construct(
		private readonly SymfonyUuid $uuid,
	) {
	}

	#[Override]
	public function toString(): string {
		return $this->uuid->__toString();
	}

	#[Override]
	public function toBinary(): string {
		return $this->uuid->toBinary();
	}

	#[Override]
	public function toRfc4122(): string {
		return $this->uuid->toRfc4122();
	}
}
