<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Enum;

/**
 * list of available algorithm when signing payload
 *
 * @experimental 31.0.0
 * @deprecated 32.0.0 use \OCP\Security\Signature\SignatureAlgorithm
 */
enum SignatureAlgorithm: string {
	/** @experimental 31.0.0
	 * @deprecated 32.0.0 use \OCP\Security\Signature\SignatureAlgorithm
	 */
	case RSA_SHA256 = 'rsa-sha256';
	/** @experimental 31.0.0
	 * @deprecated 32.0.0 use \OCP\Security\Signature\SignatureAlgorithm
	 */
	case RSA_SHA512 = 'rsa-sha512';
}
