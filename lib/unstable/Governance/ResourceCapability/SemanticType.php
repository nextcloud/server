<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability;

enum SemanticType: string {
	case Grant = 'grant';
	case Retention = 'retention';
	case Watermark = 'watermark';
}
