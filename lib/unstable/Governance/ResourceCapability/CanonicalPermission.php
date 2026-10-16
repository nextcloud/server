<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability;

enum CanonicalPermission: string {
	case Read = 'core.permission.read';
	case Update = 'core.permission.update';
	case Create = 'core.permission.create';
	case Delete = 'core.permission.delete';
}
