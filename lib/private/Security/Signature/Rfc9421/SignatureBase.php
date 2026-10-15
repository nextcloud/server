<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Rfc9421;

use InvalidArgumentException;
use OCP\Security\Signature\Exceptions\SignatureException;

/**
 * RFC 9421 §2.5 signature base construction. Implements the derived
 * components OCM uses (`@method`, `@target-uri`, `@authority`, `@scheme`,
 * `@path`, `@query`, `@request-target`) plus plain HTTP fields.
 */
final class SignatureBase {
	/**
	 * @param array<string,string> $headers headers keyed by lowercase name
	 * @param list<string> $components covered component identifiers, in order
	 * @param string $signatureParamsLine `(...);params...` for `@signature-params`
	 * @throws SignatureException when a covered field is missing from $headers
	 */
	public static function build(
		string $method,
		string $uri,
		array $headers,
		array $components,
		string $signatureParamsLine,
	): string {
		$lines = [];
		foreach ($components as $component) {
			$lines[] = '"' . $component . '": ' . self::componentValue($component, $method, $uri, $headers);
		}
		$lines[] = '"@signature-params": ' . $signatureParamsLine;
		return implode("\n", $lines);
	}

	/**
	 * Serialize `(comp...)` + `;k=v` parameters for `@signature-params` and
	 * Signature-Input dictionary entries.
	 *
	 * @param list<string> $components
	 * @param array<string, scalar> $params
	 */
	public static function serializeSignatureParams(array $components, array $params): string {
		$inner = array_map(static fn (string $c): string => '"' . $c . '"', $components);
		$out = '(' . implode(' ', $inner) . ')';
		foreach ($params as $name => $value) {
			$out .= ';' . $name . '=' . self::serializeBareItem($value);
		}
		return $out;
	}

	/**
	 * @param scalar $value
	 */
	public static function serializeBareItem(mixed $value): string {
		if (is_string($value)) {
			return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
		}
		if (is_int($value)) {
			return (string)$value;
		}
		if (is_bool($value)) {
			return $value ? '?1' : '?0';
		}
		throw new InvalidArgumentException('unsupported parameter value type');
	}

	private static function componentValue(string $component, string $method, string $uri, array $headers): string {
		if (str_starts_with($component, '@')) {
			return self::derivedValue($component, $method, $uri);
		}
		$lower = strtolower($component);
		if (!array_key_exists($lower, $headers)) {
			throw new SignatureException('missing field for signature: ' . $component);
		}
		return self::normalizeFieldValue($headers[$lower]);
	}

	private static function derivedValue(string $component, string $method, string $uri): string {
		$parts = parse_url($uri);
		if ($parts === false) {
			throw new SignatureException('cannot parse target URI');
		}
		return match ($component) {
			'@method' => strtoupper($method),
			'@target-uri' => $uri,
			'@authority' => self::authority($parts),
			'@scheme' => strtolower($parts['scheme'] ?? ''),
			'@path' => $parts['path'] ?? '/',
			'@query' => isset($parts['query']) ? '?' . $parts['query'] : '',
			'@request-target' => ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''),
			default => throw new SignatureException('unsupported derived component: ' . $component),
		};
	}

	private static function authority(array $parts): string {
		$host = strtolower((string)($parts['host'] ?? ''));
		if ($host === '') {
			return '';
		}
		$port = $parts['port'] ?? null;
		$scheme = strtolower((string)($parts['scheme'] ?? ''));
		// RFC 9421 §2.2.3: default ports are omitted.
		if ($port !== null && !self::isDefaultPort($scheme, (int)$port)) {
			return $host . ':' . $port;
		}
		return $host;
	}

	private static function isDefaultPort(string $scheme, int $port): bool {
		return ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
	}

	private static function normalizeFieldValue(string $value): string {
		// RFC 9421 §2.1: strip OWS, collapse internal whitespace.
		return preg_replace('/[ \t]+/', ' ', trim($value)) ?? '';
	}
}
