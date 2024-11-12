<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

/**
 * current status of signatory. is it trustable or not ?
 *
 * - SYNCED = the remote instance is trustable.
 * - BROKEN = the remote instance does not use the same key pairs
 *
 * @experimental 31.0.0
 * @since 31.0.0
 */
enum SignatoryStatus: int {
	/** @since 31.0.0 */
	case SYNCED = 1;
	/** @since 31.0.0 */
	case BROKEN = 9;
}
