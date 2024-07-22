<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Signature;

/**
 * list of available algorithm when signing payload
 *
 * @since 30.0.0
 */
enum SignatureAlgorithm: string {
	/** @since 30.0.0 */
	case SHA256 = 'sha256';
	/** @since 30.0.0 */
	case SHA512 = 'sha512';
}
