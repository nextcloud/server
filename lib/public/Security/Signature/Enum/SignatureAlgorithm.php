<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Signature\Enum;

use OCP\AppFramework\Attribute\Consumable;

/**
 * list of available algorithm when signing payload
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
enum SignatureAlgorithm: string {
	/** @since 33.0.0 */
	case RSA_SHA256 = 'rsa-sha256';
	/** @since 33.0.0 */
	case RSA_SHA512 = 'rsa-sha512';
}
