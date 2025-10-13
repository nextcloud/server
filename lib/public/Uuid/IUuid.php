<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Uuid;

use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '33.0.0')]
interface IUuid {
	public function toString(): string;

	public function toBinary(): string;

	public function toRfc4122(): string;
}
