<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Rfc9421;

use Firebase\JWT\Key;
use OCP\Security\Signature\ISignatoryManager;

/**
 * Capability bit for {@see ISignatoryManager} implementations that can resolve
 * a remote JWK for RFC 9421 verification. {@see \OC\Security\Signature\SignatureManager}
 * checks this via instanceof on the RFC 9421 path; cavage doesn't need it.
 */
interface IJwkResolvingSignatoryManager extends ISignatoryManager {
	/**
	 * Resolve the JWK identified by $keyId for the remote at $origin and
	 * return it as a parsed {@see Key}. Null when no matching JWK is found.
	 *
	 * @param string $origin host of the remote that signed the request
	 * @param string $keyId raw `keyid` from Signature-Input; matched against JWK `kid`
	 */
	public function getRemoteKey(string $origin, string $keyId): ?Key;
}
