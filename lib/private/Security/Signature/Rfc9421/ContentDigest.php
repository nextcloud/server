<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Rfc9421;

use InvalidArgumentException;

/** RFC 9530 `Content-Digest` helpers; covered by RFC 9421 §7.2.5 in OCM signatures. */
final class ContentDigest {
	public const ALGO_SHA256 = 'sha-256';
	public const ALGO_SHA512 = 'sha-512';

	public static function compute(string $body, string $algorithm = self::ALGO_SHA256): string {
		$hashAlgorithm = self::hashAlgorithmFor($algorithm);
		return $algorithm . '=:' . base64_encode(hash($hashAlgorithm, $body, true)) . ':';
	}

	/**
	 * True iff at least one recognised algorithm matches and none mismatch.
	 * Stricter than RFC 9530 §2's "any-match"; OCM treats mismatches as an
	 * attack on the weaker algorithm.
	 */
	public static function verify(string $header, string $body): bool {
		$matched = false;
		foreach (self::parse($header) as $algorithm => $digest) {
			try {
				$hashAlgorithm = self::hashAlgorithmFor($algorithm);
			} catch (InvalidArgumentException) {
				continue;
			}
			if (!hash_equals(hash($hashAlgorithm, $body, true), $digest)) {
				return false;
			}
			$matched = true;
		}
		return $matched;
	}

	/** @return array<string, string> [algorithm => raw bytes] */
	public static function parse(string $header): array {
		$out = [];
		foreach (explode(',', $header) as $entry) {
			$entry = trim($entry);
			if ($entry === '') {
				continue;
			}
			if (!preg_match('#^([a-z0-9-]+)=:([A-Za-z0-9+/=]*):$#', $entry, $m)) {
				continue;
			}
			$decoded = base64_decode($m[2], true);
			if ($decoded === false) {
				continue;
			}
			$out[strtolower($m[1])] = $decoded;
		}
		return $out;
	}

	private static function hashAlgorithmFor(string $algorithm): string {
		return match (strtolower($algorithm)) {
			self::ALGO_SHA256 => 'sha256',
			self::ALGO_SHA512 => 'sha512',
			default => throw new InvalidArgumentException('unsupported content-digest algorithm: ' . $algorithm),
		};
	}
}
