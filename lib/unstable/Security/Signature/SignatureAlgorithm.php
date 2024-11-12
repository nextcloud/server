<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

/**
 * list of available algorithm when signing payload
 *
 * @experimental 31.0.0
 * @since 31.0.0
 */
enum SignatureAlgorithm: string {
	/** @since 31.0.0 */
	case SHA256 = 'sha256';
	/** @since 31.0.0 */
	case SHA512 = 'sha512';
}
