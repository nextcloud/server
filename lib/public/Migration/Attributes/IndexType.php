<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

/**
 * type of index
 *
 * @since 30.0.0
 */
enum IndexType : string {
	/** @since 30.0.0 */
	case PRIMARY = 'primary';
	/** @since 30.0.0 */
	case INDEX = 'index';
	/** @since 30.0.0 */
	case UNIQUE = 'unique';
}
