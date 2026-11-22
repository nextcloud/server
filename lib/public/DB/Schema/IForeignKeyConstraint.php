<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Schema;

/**
 * Object representation of a foreign key constraint.
 *
 * @since 35.0.0
 */

interface IForeignKeyConstraint {
	/**
	 * Return the name of the foreign key constraint
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function getName(): string;
}
