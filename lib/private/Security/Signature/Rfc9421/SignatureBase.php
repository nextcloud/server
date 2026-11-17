<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Rfc9421;

use gapple\StructuredFields\Dictionary;
use gapple\StructuredFields\ParseException;
use gapple\StructuredFields\Parser;
use gapple\StructuredFields\Serializer;
use gapple\StructuredFields\TupleInterface;
use InvalidArgumentException;
use OCP\IRequest;
use OCP\Security\Signature\Exceptions\SignatureException;

/**
 * RFC 9421 §2.5 signature base construction. Implements the derived
 * components OCM uses (`@method`, `@target-uri`, `@authority`, `@scheme`,
 * `@path`, `@query`, `@request-target`, `@status`) plus plain HTTP fields.
 *
 * Component identifiers may carry the `;req` parameter (component of the
 * triggering request, only valid in response signatures) and the
 * `;key="..."` parameter (a single member of a structured field, e.g. one
 * entry of the Signature-Input dictionary).
 */
final class SignatureBase {
	public const DEFAULT_SIGNING_ALGORITHM = 'ecdsa-p256-sha256';
	public const OCM_SIGNATURE_LABEL = 'ocm';
	public const OCM_SIGNATURE_TAG = 'ocm';

	/**
	 * @param array<string,string> $headers headers keyed by lowercase name
	 * @param list<string> $components covered component identifiers, in order
	 * @param string $signatureParamsLine `(...);params...` for `@signature-params`
	 * @throws SignatureException when a component identifier is malformed or a covered field is missing from $headers
	 */
	public static function build(
		string $method,
		string $uri,
		array $headers,
		array $components,
		string $signatureParamsLine,
	): string {
		return self::buildBase($components, $signatureParamsLine, static function (string $name, bool $req, ?string $key) use ($method, $uri, $headers): string {
			if ($req) {
				throw new SignatureException(';req is only valid in response signatures: ' . $name);
			}
			return self::requestComponentValue($name, $key, $method, $uri, $headers);
		});
	}

	/**
	 * Build a response signature base with optional components from the
	 * request that triggered the response.
	 *
	 * @param array<string,string> $responseHeaders response headers keyed by lowercase name
	 * @param array<string,string> $requestHeaders request headers keyed by lowercase name
	 * @param list<string> $components covered component identifiers, in order
	 * @param string $signatureParamsLine `(...);params...` for `@signature-params`
	 * @throws SignatureException when a component identifier is malformed or a covered field is missing
	 */
	public static function buildResponse(
		int $status,
		array $responseHeaders,
		string $requestMethod,
		string $requestUri,
		array $requestHeaders,
		array $components,
		string $signatureParamsLine,
	): string {
		return self::buildBase($components, $signatureParamsLine, static function (string $name, bool $req, ?string $key) use ($status, $responseHeaders, $requestMethod, $requestUri, $requestHeaders): string {
			if ($req) {
				return self::requestComponentValue($name, $key, $requestMethod, $requestUri, $requestHeaders);
			}
			if ($key !== null) {
				throw new SignatureException(';key is only valid together with ;req: ' . $name);
			}
			return self::responseComponentValue($name, $status, $responseHeaders);
		});
	}

	/**
	 * @param list<string> $components
	 * @param callable(string, bool, ?string): string $valueResolver receives (name, req, key)
	 */
	private static function buildBase(array $components, string $signatureParamsLine, callable $valueResolver): string {
		$lines = [];
		foreach ($components as $component) {
			[$name, $req, $key] = self::parseComponentIdentifier($component);
			$lines[] = self::serializeComponentIdentifier($name, $req, $key) . ': ' . $valueResolver($name, $req, $key);
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
	 * @throws SignatureException when a component identifier is malformed
	 */
	public static function serializeSignatureParams(array $components, array $params): string {
		$inner = [];
		foreach ($components as $component) {
			[$name, $req, $key] = self::parseComponentIdentifier($component);
			$inner[] = self::serializeComponentIdentifier($name, $req, $key);
		}
		$out = '(' . implode(' ', $inner) . ')';
		foreach ($params as $name => $value) {
			$out .= ';' . $name . '=' . self::serializeBareItem($value);
		}
		return $out;
	}

	/**
	 * Parse a component identifier into name and parameters.
	 *
	 * @return array{string, bool, ?string} [name, req, key]
	 * @throws SignatureException on malformed identifiers
	 */
	public static function parseComponentIdentifier(string $component): array {
		$len = strlen($component);
		$i = 0;
		while ($i < $len && $component[$i] !== ';') {
			$i++;
		}
		$name = substr($component, 0, $i);
		if (!preg_match('/^(?:@[a-z][a-z0-9-]*|[a-z*][a-z0-9_*-]*(?:\.[a-z0-9_*-]+)*)$/', $name)) {
			throw new SignatureException('invalid component identifier: ' . $component);
		}

		$req = false;
		$key = null;
		while ($i < $len) {
			$i++; // consume ';'
			$paramStart = $i;
			while ($i < $len && $component[$i] !== '=' && $component[$i] !== ';') {
				$i++;
			}
			$paramName = substr($component, $paramStart, $i - $paramStart);
			$paramValue = true;
			if ($i < $len && $component[$i] === '=') {
				$i++;
				if ($i < $len && $component[$i] === '"') {
					$i++;
					$value = '';
					while ($i < $len && $component[$i] !== '"') {
						if ($component[$i] === '\\' && $i + 1 < $len) {
							$value .= $component[$i + 1];
							$i += 2;
							continue;
						}
						$value .= $component[$i];
						$i++;
					}
					if ($i >= $len) {
						throw new SignatureException('unterminated string in component identifier: ' . $component);
					}
					$i++; // closing quote
					$paramValue = $value;
				} else {
					$valueStart = $i;
					while ($i < $len && $component[$i] !== ';') {
						$i++;
					}
					$paramValue = substr($component, $valueStart, $i - $valueStart);
				}
			}
			switch ($paramName) {
				case 'req':
					if ($paramValue !== true) {
						throw new SignatureException(';req takes no value: ' . $component);
					}
					$req = true;
					break;
				case 'key':
					if (!is_string($paramValue) || $paramValue === '') {
						throw new SignatureException(';key requires a non-empty string value: ' . $component);
					}
					$key = $paramValue;
					break;
				default:
					throw new SignatureException('unsupported component identifier parameter ";' . $paramName . '": ' . $component);
			}
		}
		return [$name, $req, $key];
	}

	/** Serialize a parsed component identifier, e.g. `"signature-input";key="ocm";req`. */
	public static function serializeComponentIdentifier(string $name, bool $req, ?string $key): string {
		$out = '"' . $name . '"';
		if ($key !== null) {
			$out .= ';key=' . self::serializeBareItem($key);
		}
		if ($req) {
			$out .= ';req';
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

	/** Reconstruct the absolute target URI of an incoming request. */
	public static function reconstructTargetUri(IRequest $request): string {
		return $request->getServerProtocol() . '://' . $request->getServerHost() . $request->getRequestUri();
	}

	/**
	 * RFC 9421 alg name (e.g. `ecdsa-p256-sha256`) from the signatory manager options.
	 *
	 * @param array<string, mixed> $options
	 */
	public static function resolveSigningAlgorithm(array $options): string {
		return (string)($options['rfc9421.signingAlgorithm'] ?? self::DEFAULT_SIGNING_ALGORITHM);
	}

	/**
	 * Signature parameters of an OCM signature: `created`, `keyid` and the
	 * integrity-protected `tag="ocm"`, plus `alg` when
	 * `rfc9421.includeAlgParameter` is set (off by default, RFC 9421 §3.3.7).
	 *
	 * @param array<string, mixed> $options signatory manager options
	 * @return array<string, scalar>
	 */
	public static function buildSignatureParams(array $options, string $keyId, string $signingAlgorithm): array {
		$params = [
			'created' => time(),
			'keyid' => $keyId,
			'tag' => self::OCM_SIGNATURE_TAG,
		];
		if ((bool)($options['rfc9421.includeAlgParameter'] ?? false)) {
			$params['alg'] = $signingAlgorithm;
		}
		return $params;
	}

	/**
	 * Signature-Input / Signature header values for one signature label.
	 *
	 * @return array{0: string, 1: string}
	 */
	public static function formatSignatureHeaders(string $label, string $signatureParamsLine, string $rawSignature): array {
		return [
			$label . '=' . $signatureParamsLine,
			$label . '=:' . base64_encode($rawSignature) . ':',
		];
	}

	private static function requestComponentValue(string $name, ?string $key, string $method, string $uri, array $headers): string {
		if (str_starts_with($name, '@')) {
			if ($key !== null) {
				throw new SignatureException(';key is not allowed on derived component: ' . $name);
			}
			return self::derivedValue($name, $method, $uri);
		}
		$lower = strtolower($name);
		if (!array_key_exists($lower, $headers)) {
			throw new SignatureException('missing field for signature: ' . $name);
		}
		$value = $headers[$lower];
		if ($key !== null) {
			return self::dictionaryMemberValue($lower, $key, $value);
		}
		return self::normalizeFieldValue($value);
	}

	private static function responseComponentValue(string $name, int $status, array $headers): string {
		if ($name === '@status') {
			return (string)$status;
		}
		if (str_starts_with($name, '@')) {
			throw new SignatureException('unsupported response derived component: ' . $name);
		}
		$lower = strtolower($name);
		if (!array_key_exists($lower, $headers)) {
			throw new SignatureException('missing field for signature: ' . $name);
		}
		return self::normalizeFieldValue($headers[$lower]);
	}

	/**
	 * Value of a `;key="..."` component: the single dictionary member
	 * `label=item` of a structured field (RFC 8941).
	 */
	private static function dictionaryMemberValue(string $field, string $key, string $value): string {
		try {
			$dict = Parser::parseDictionary($value);
		} catch (ParseException $e) {
			throw new SignatureException('field ' . $field . ' is not a valid dictionary: ' . $e->getMessage(), 0, $e);
		}
		$entry = null;
		foreach ($dict as $label => $item) {
			if ($label === $key) {
				$entry = $item;
				break;
			}
		}
		if ($entry === null) {
			throw new SignatureException('dictionary entry "' . $key . '" missing in field: ' . $field);
		}
		if ($entry === true) {
			return $key;
		}
		if (!$entry instanceof TupleInterface) {
			throw new SignatureException('unsupported dictionary entry for "' . $key . '" in field: ' . $field);
		}
		return Serializer::serializeDictionary(Dictionary::fromArray([$key => $entry]));
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
