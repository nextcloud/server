<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Schema;

use Doctrine\DBAL\Types\Type as DBALType;
use OCP\DB\Schema\IType;

/**
 * Object representation of a column type, wrapping a Doctrine DBAL Type.
 *
 * Only exposes `getName()`, to keep 3rdparty apps that call
 * `$column->getType()->getName()` (the Doctrine DBAL API) working, without
 * committing to the rest of Doctrine's Type API as public API.
 */
class Type implements IType {
	public function __construct(
		private DBALType $type,
	) {
	}

	/**
	 * Returns the wrapped Doctrine DBAL type.
	 */
	public function getWrappedType(): DBALType {
		return $this->type;
	}

	#[\Override]
	public function getName(): string {
		return $this->type->getName();
	}
}
