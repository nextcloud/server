<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\IdentityProof;

use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '36.0.0')]
enum Algorithm: string {
	#[\Deprecated(message: 'Not post-quantum save, please transition to another algorithm', since: '36.0.0')]
	case Rsa2048 = 'rsa2048';
	#[\Deprecated(message: 'Not post-quantum save, please transition to another algorithm', since: '36.0.0')]
	case Es256 = 'es256';
	case Es384 = 'es384';
}
