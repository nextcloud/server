<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\IdentityProof;

enum KeyType: string {
	case System = 'system';
	case User = 'user';
	case App = 'app';
}
