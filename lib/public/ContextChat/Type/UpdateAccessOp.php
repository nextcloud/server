<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\ContextChat\Type;

/**
 * @since 32.0.0
 */
class UpdateAccessOp {
	public const ALLOW = 'allow';
	public const DENY = 'deny';
}
