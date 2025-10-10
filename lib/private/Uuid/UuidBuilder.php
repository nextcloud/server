<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Uuid;

use OCP\Uuid\IUuid;
use OCP\Uuid\IUuidBuilder;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * @internal
 */
class UuidBuilder implements IUuidBuilder {

	public function v7(): IUuid {
		return new Uuid(SymfonyUuid::v7());
	}

	public function fromString(string $uuid): IUuid {
		return new Uuid(SymfonyUuid::fromString($uuid));
	}
}
