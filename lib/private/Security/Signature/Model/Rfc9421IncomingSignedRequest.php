<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Model;

use Firebase\JWT\Key;
use gapple\StructuredFields\Bytes;
use gapple\StructuredFields\InnerList;
use gapple\StructuredFields\Item;
use gapple\StructuredFields\Parameters;
use gapple\StructuredFields\ParseException;
use gapple\StructuredFields\Parser;
use gapple\StructuredFields\Token;
use JsonSerializable;
use OC\Security\Signature\Rfc9421\Algorithm;
use OC\Security\Signature\Rfc9421\ContentDigest;
use OC\Security\Signature\Rfc9421\SignatureBase;
use OC\Security\Signature\SignatureManager;
use OCP\IRequest;
use OCP\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\InvalidSignatureException;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\Exceptions\SignatureNotFoundException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Security\Signature\Model\Signatory;

/**
 * RFC 9421 implementation of {@see IIncomingSignedRequest}. Parses the
 * inbound Signature-Input / Signature dictionaries, picks the OCM-labeled
 * entry (RFC 9421 §3.2 lets verifiers scope by policy), and rebuilds the
 * signature base per RFC 9421 §2.5. Crypto is deferred to {@see verify()},
 * which needs a {@see Key} attached via {@see setKey()}. Body integrity
 * (RFC 9530 content-digest) is checked before verify() if covered.
 */
class Rfc9421IncomingSignedRequest extends SignedRequest implements
	IIncomingSignedRequest,
	JsonSerializable {
	/** Baseline cover for OCM. Override via `rfc9421.requiredComponents`. */
	private const DEFAULT_REQUIRED_COMPONENTS = [
		'@method',
		'@target-uri',
		'content-digest',
		'content-length',
		'date',
	];

	/** Max clock skew (seconds) for `created`. Override via `rfc9421.maxClockSkew`. */
	private const DEFAULT_MAX_FUTURE_SKEW = 60;

	private string $origin = '';
	/** @var list<string> */
	private array $components;
	/** @var array<string, scalar> */
	private array $signatureParams;
	private string $signatureBaseString;
	private string $rawSignature;
	private ?Key $key = null;

	/**
	 * @throws IncomingRequestException if anything looks wrong with the request structure
	 * @throws SignatureNotFoundException if the request is not signed
	 * @throws SignatureException if signature metadata is malformed or covered components reference missing fields
	 */
	public function __construct(
		string $body,
		private readonly IRequest $request,
		private readonly array $options = [],
	) {
		parent::__construct($body);

		$signatureInputHeader = $request->getHeader('Signature-Input');
		$signatureHeader = $request->getHeader('Signature');
		if ($signatureInputHeader === '') {
			throw new SignatureNotFoundException('missing Signature-Input header');
		}
		if ($signatureHeader === '') {
			throw new SignatureNotFoundException('missing Signature header');
		}

		$inputs = self::parseSignatureInput($signatureInputHeader);
		$signatures = self::parseSignature($signatureHeader);

		// OCM policy (stricter than RFC 8941 §4.2 last-wins): a duplicate
		// `ocm` entry is ambiguous; the entire request MUST be rejected.
		if (self::countLabel($signatureInputHeader, 'ocm') > 1
			|| self::countLabel($signatureHeader, 'ocm') > 1) {
			throw new IncomingRequestException(
				'multiple "' . 'ocm' . '" entries in signature headers'
			);
		}

		if (!isset($inputs['ocm'])) {
			throw new SignatureNotFoundException('missing "' . 'ocm' . '" entry in Signature-Input');
		}
		if (!isset($signatures['ocm'])) {
			throw new SignatureNotFoundException('missing "' . 'ocm' . '" entry in Signature');
		}

		$entry = $inputs['ocm'];
		$this->components = $entry['components'];
		$this->signatureParams = $entry['params'];
		$this->rawSignature = $signatures['ocm'];

		$this->verifyRequiredComponents();
		$this->verifyTimestamps();
		$this->verifyContentDigestIfCovered($body);
		$this->verifyContentLengthIfCovered($body);

		$keyId = $this->signatureParams['keyid'] ?? null;
		if (!is_string($keyId) || $keyId === '') {
			throw new IncomingRequestException('missing keyid in Signature-Input');
		}
		try {
			$this->origin = Signatory::extractIdentityFromUri($keyId);
		} catch (IdentityNotFoundException) {
			// keyid may follow the OCM convention `<fqdn>#<id>`; the OCM layer
			// derives origin from the message body in that case.
			$this->origin = '';
		}

		$paramsLine = SignatureBase::serializeSignatureParams($this->components, $this->signatureParams);
		$this->signatureBaseString = SignatureBase::build(
			$request->getMethod(),
			$this->reconstructTargetUri(),
			$this->collectHeaders(),
			$this->components,
			$paramsLine,
		);

		$this->setSigningElements([
			'label' => 'ocm',
			'keyId' => $keyId,
			'algorithm' => isset($this->signatureParams['alg']) ? (string)$this->signatureParams['alg'] : '',
			'created' => isset($this->signatureParams['created']) ? (string)$this->signatureParams['created'] : '',
			'components' => implode(' ', $this->components),
			'params' => $paramsLine,
			'signature' => base64_encode($this->rawSignature),
		]);
		$this->setSignature(base64_encode($this->rawSignature));
		$this->setSignatureData([$this->signatureBaseString]);
	}

	#[\Override]
	public function getRequest(): IRequest {
		return $this->request;
	}

	#[\Override]
	public function getOrigin(): string {
		if ($this->origin === '') {
			throw new IncomingRequestException('empty origin');
		}
		return $this->origin;
	}

	#[\Override]
	public function getKeyId(): string {
		return $this->getSigningElement('keyId');
	}

	/** Required before {@see verify()} is called. */
	public function setKey(Key $key): self {
		$this->key = $key;
		return $this;
	}

	public function getKey(): ?Key {
		return $this->key;
	}

	/** Signature-Input `alg` if present, else null (RFC 9421 §3.3.7 omitted-alg path). */
	public function getAlgorithm(): ?string {
		return isset($this->signatureParams['alg']) ? (string)$this->signatureParams['alg'] : null;
	}

	/**
	 * @return array<string, scalar>
	 */
	public function getSignatureParams(): array {
		return $this->signatureParams;
	}

	/**
	 * @return list<string>
	 */
	public function getCoveredComponents(): array {
		return $this->components;
	}

	public function getSignatureBaseString(): string {
		return $this->signatureBaseString;
	}

	#[\Override]
	public function verify(): void {
		if ($this->key === null) {
			throw new SignatoryNotFoundException('no JWK set for verification');
		}
		try {
			$ok = Algorithm::verify(
				$this->signatureBaseString,
				$this->rawSignature,
				$this->key,
				$this->getAlgorithm(),
			);
		} catch (SignatureException $e) {
			throw new InvalidSignatureException($e->getMessage(), 0, $e);
		}
		if (!$ok) {
			throw new InvalidSignatureException('signature verification failed');
		}
	}

	/** @throws IncomingRequestException if the signature doesn't cover the OCM-required components */
	private function verifyRequiredComponents(): void {
		/** @var list<string> $required */
		$required = $this->options['rfc9421.requiredComponents'] ?? self::DEFAULT_REQUIRED_COMPONENTS;
		$missing = array_values(array_diff($required, $this->components));
		if ($missing !== []) {
			throw new IncomingRequestException(
				'signature does not cover required components: ' . implode(', ', $missing)
			);
		}
	}

	/** @throws IncomingRequestException on stale, future-dated, or missing `created` */
	private function verifyTimestamps(): void {
		$ttl = (int)($this->options['ttl'] ?? SignatureManager::DATE_TTL);
		$skew = (int)($this->options['rfc9421.maxClockSkew'] ?? self::DEFAULT_MAX_FUTURE_SKEW);
		$now = time();

		if (!isset($this->signatureParams['created'])) {
			throw new IncomingRequestException('signature missing required `created` parameter');
		}
		$created = (int)$this->signatureParams['created'];
		if ($created > $now + $skew) {
			throw new IncomingRequestException('signature `created` is too far in the future');
		}
		if ($ttl > 0 && $created < $now - $ttl) {
			throw new IncomingRequestException('signature is too old');
		}

		if (isset($this->signatureParams['expires'])) {
			$expires = (int)$this->signatureParams['expires'];
			if ($expires < $now) {
				throw new IncomingRequestException('signature has expired');
			}
		}
	}

	private function verifyContentDigestIfCovered(string $body): void {
		if (!in_array('content-digest', $this->components, true)) {
			return;
		}
		$header = $this->request->getHeader('Content-Digest');
		if ($header === '') {
			throw new IncomingRequestException('content-digest covered but missing from request');
		}
		if (!ContentDigest::verify($header, $body)) {
			throw new IncomingRequestException('content-digest does not match body');
		}
	}

	private function verifyContentLengthIfCovered(string $body): void {
		if (!in_array('content-length', $this->components, true)) {
			return;
		}
		$header = $this->request->getHeader('Content-Length');
		if ($header === '') {
			throw new IncomingRequestException('content-length covered but missing from request');
		}
		if ((int)$header !== strlen($body)) {
			throw new IncomingRequestException('content-length does not match body size');
		}
	}

	private function reconstructTargetUri(): string {
		$scheme = $this->request->getServerProtocol();
		$host = $this->request->getServerHost();
		$path = $this->request->getRequestUri();
		return $scheme . '://' . $host . $path;
	}

	/**
	 * Collect the HTTP request fields covered by the signature, keyed by their
	 * lowercased name. Derived components (`@*`) are produced inside
	 * {@see SignatureBase}; we only collect plain fields here.
	 *
	 * @return array<string, string>
	 */
	private function collectHeaders(): array {
		$out = [];
		foreach ($this->components as $component) {
			if (str_starts_with($component, '@')) {
				continue;
			}
			$value = $this->request->getHeader($component);
			if ($value === '' && strtolower($component) === 'host') {
				$value = $this->request->getServerHost();
			}
			$out[strtolower($component)] = $value;
		}
		return $out;
	}

	#[\Override]
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'origin' => $this->origin,
				'label' => 'ocm',
				'components' => $this->components,
				'signatureParams' => $this->signatureParams,
				'signatureBase' => $this->signatureBaseString,
			]
		);
	}

	/**
	 * @return array<string, array{components: list<string>, params: array<string, scalar>}>
	 * @throws SignatureException
	 */
	private static function parseSignatureInput(string $header): array {
		try {
			$dict = Parser::parseDictionary($header);
		} catch (ParseException $e) {
			throw new SignatureException('malformed Signature-Input: ' . $e->getMessage(), 0, $e);
		}

		$out = [];
		foreach ($dict as $label => $entry) {
			if (!$entry instanceof InnerList) {
				throw new SignatureException('Signature-Input value for ' . $label . ' is not an inner list');
			}
			$components = [];
			foreach ($entry->getValue() as $item) {
				$value = $item->getValue();
				if (!is_string($value)) {
					throw new SignatureException('component identifier in Signature-Input must be a string');
				}
				$components[] = $value;
			}
			$parameters = $entry->getParameters();
			if (!$parameters instanceof Parameters) {
				throw new SignatureException('Signature-Input parameters for ' . $label . ' are not iterable');
			}
			$out[$label] = [
				'components' => $components,
				'params' => self::normalizeParameters($parameters),
			];
		}
		return $out;
	}

	/**
	 * @return array<string, string> raw signature bytes keyed by label
	 * @throws SignatureException
	 */
	private static function parseSignature(string $header): array {
		try {
			$dict = Parser::parseDictionary($header);
		} catch (ParseException $e) {
			throw new SignatureException('malformed Signature: ' . $e->getMessage(), 0, $e);
		}

		$out = [];
		foreach ($dict as $label => $entry) {
			if (!$entry instanceof Item || !$entry->getValue() instanceof Bytes) {
				throw new SignatureException('Signature value for ' . $label . ' is not a byte sequence');
			}
			$out[$label] = (string)$entry->getValue();
		}
		return $out;
	}

	/**
	 * @param iterable<string, mixed> $parameters
	 * @return array<string, scalar>
	 */
	private static function normalizeParameters(iterable $parameters): array {
		$out = [];
		foreach ($parameters as $name => $value) {
			$out[(string)$name] = match (true) {
				is_string($value), is_int($value), is_bool($value) => $value,
				$value instanceof Token => (string)$value,
				default => throw new SignatureException('unsupported parameter type for ' . $name),
			};
		}
		return $out;
	}

	/** Count $label occurrences in a dictionary header (gapple collapses dups per RFC 8941 §4.2). */
	private static function countLabel(string $header, string $label): int {
		$count = 0;
		$len = strlen($header);
		$i = 0;
		while ($i < $len) {
			while ($i < $len && ($header[$i] === ' ' || $header[$i] === "\t")) {
				$i++;
			}
			$start = $i;
			while ($i < $len) {
				$c = $header[$i];
				if (!ctype_lower($c) && !ctype_digit($c) && $c !== '*' && $c !== '_' && $c !== '-' && $c !== '.') {
					break;
				}
				$i++;
			}
			if ($i === $start) {
				break;
			}
			if (substr($header, $start, $i - $start) === $label) {
				$count++;
			}
			// Skip to next top-level comma; track strings, byte-sequences, parens.
			$inString = false;
			$inByteSeq = false;
			$depth = 0;
			while ($i < $len) {
				$c = $header[$i];
				if ($inString) {
					if ($c === '\\' && $i + 1 < $len) {
						$i += 2;
						continue;
					}
					if ($c === '"') {
						$inString = false;
					}
					$i++;
					continue;
				}
				if ($inByteSeq) {
					if ($c === ':') {
						$inByteSeq = false;
					}
					$i++;
					continue;
				}
				if ($c === '"') {
					$inString = true;
				} elseif ($c === ':') {
					$inByteSeq = true;
				} elseif ($c === '(') {
					$depth++;
				} elseif ($c === ')') {
					$depth--;
				} elseif ($c === ',' && $depth === 0) {
					$i++;
					break;
				}
				$i++;
			}
		}
		return $count;
	}
}
