<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Schema;

use OCP\DB\Types;

/**
 * Object representation of a column type.
 *
 * @since 35.0.0
 */
interface IType {
	/**
	 * Returns the name of this type.
	 *
	 * @return Types::*
	 *
	 * @since 35.0.0
	 */
	public function getName(): string;
}
