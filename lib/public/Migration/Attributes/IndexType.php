<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

/**
 * @since 30.0.0
 */
enum IndexType : string {
	/** @since 30.0.0 */
	case PRIMARY = 'primary'; // migration is estimated to require few minutes
	/** @since 30.0.0 */
	case INDEX = 'index'; // depends on setup, migration might require some time
	/** @since 30.0.0 */
	case UNIQUE = 'unique'; // migration should be light and quick
}
