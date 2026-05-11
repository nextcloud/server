<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Model;

use JsonSerializable;
use OC\Security\Signature\Rfc9421\Algorithm;
use OC\Security\Signature\Rfc9421\ContentDigest;
use OC\Security\Signature\Rfc9421\SignatureBase;
use OC\Security\Signature\SignatureManager;
use OCP\Security\Signature\Enum\DigestAlgorithm;
use OCP\Security\Signature\Enum\SignatureAlgorithm;
use OCP\Security\Signature\Exceptions\SignatoryException;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\IOutgoingSignedRequest;
use OCP\Security\Signature\ISignatoryManager;

/**
 * RFC 9421 implementation of {@see IOutgoingSignedRequest}, sibling to the
 * draft-cavage {@see OutgoingSignedRequest}. Default ECDSA P-256 (`ES256`)
 * with the `alg` parameter omitted (RFC 9421 §3.3.7); verifier resolves it
 * from the JWK.
 *
 * Options from {@see ISignatoryManager::getOptions()}: `rfc9421.signingAlgorithm`,
 * `rfc9421.coveredComponents`, `rfc9421.contentDigestAlgorithm`,
 * `rfc9421.includeAlgParameter`, `dateHeader`.
 */
class Rfc9421OutgoingSignedRequest extends SignedRequest implements
	IOutgoingSignedRequest,
	JsonSerializable {
	private const DEFAULT_COMPONENTS = ['@method', '@target-uri', 'content-digest', 'content-length', 'date'];

	private string $host = '';
	private array $headers = [];
	/** @var list<string> $headerList */
	private array $headerList = [];
	private SignatureAlgorithm $algorithm;
	private string $signingAlgorithm;
	/** @var array<string, scalar> */
	private array $signatureParams;
	private string $signatureBaseString;

	public function __construct(
		string $body,
		ISignatoryManager $signatoryManager,
		private readonly string $identity,
		private readonly string $method,
		private readonly string $uri,
	) {
		parent::__construct($body);

		$options = $signatoryManager->getOptions();
		$this->setHost($identity)
			->setAlgorithm($options['algorithm'] ?? SignatureAlgorithm::RSA_SHA256)
			->setSignatory($signatoryManager->getLocalSignatory())
			->setDigestAlgorithm($options['digestAlgorithm'] ?? DigestAlgorithm::SHA256);

		$this->signingAlgorithm = (string)($options['rfc9421.signingAlgorithm'] ?? 'ecdsa-p256-sha256');
		$contentDigestAlgorithm = (string)($options['rfc9421.contentDigestAlgorithm'] ?? ContentDigest::ALGO_SHA256);
		/** @var list<string> $components */
		$components = $options['rfc9421.coveredComponents'] ?? self::DEFAULT_COMPONENTS;
		$includeAlg = (bool)($options['rfc9421.includeAlgParameter'] ?? false);
		$dateHeaderFormat = (string)($options['dateHeader'] ?? SignatureManager::DATE_HEADER);

		$this->addHeader('Content-Digest', ContentDigest::compute($body, $contentDigestAlgorithm))
			->addHeader('Content-Length', strlen($body))
			->addHeader('Date', gmdate($dateHeaderFormat));
		if (in_array('host', $components, true)) {
			$this->addHeader('Host', $this->host);
		}

		$this->setHeaderList($components);
		$this->signatureParams = [
			'created' => time(),
			'keyid' => $this->getSignatory()->getKeyId(),
		];
		if ($includeAlg) {
			// Off by default per RFC 9421 §3.3.7 (verifier resolves alg from JWK).
			$this->signatureParams['alg'] = $this->signingAlgorithm;
		}

		$this->signatureBaseString = SignatureBase::build(
			$this->method,
			$this->uri,
			$this->headersByLowercaseName(),
			$this->headerList,
			SignatureBase::serializeSignatureParams($this->headerList, $this->signatureParams)
		);
		$this->setSignatureData([$this->signatureBaseString]);
	}

	#[\Override]
	public function setHost(string $host): self {
		$this->host = $host;
		return $this;
	}

	#[\Override]
	public function getHost(): string {
		return $this->host;
	}

	#[\Override]
	public function addHeader(string $key, string|int|float $value): self {
		$this->headers[$key] = $value;
		return $this;
	}

	#[\Override]
	public function getHeaders(): array {
		return $this->headers;
	}

	#[\Override]
	public function setHeaderList(array $list): self {
		$this->headerList = $list;
		return $this;
	}

	#[\Override]
	public function getHeaderList(): array {
		return $this->headerList;
	}

	#[\Override]
	public function setAlgorithm(SignatureAlgorithm $algorithm): self {
		$this->algorithm = $algorithm;
		return $this;
	}

	#[\Override]
	public function getAlgorithm(): SignatureAlgorithm {
		return $this->algorithm;
	}

	/** RFC 9421 alg name (e.g. `ecdsa-p256-sha256`). Distinct from cavage's {@see getAlgorithm()}. */
	public function getSigningAlgorithm(): string {
		return $this->signingAlgorithm;
	}

	public function getSignatureBaseString(): string {
		return $this->signatureBaseString;
	}

	#[\Override]
	public function sign(): self {
		$privateKey = $this->getSignatory()->getPrivateKey();
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		$rawSignature = Algorithm::sign(
			$this->signatureBaseString,
			$privateKey,
			$this->signingAlgorithm,
		);
		$this->setSignature(base64_encode($rawSignature));

		$paramsLine = SignatureBase::serializeSignatureParams($this->headerList, $this->signatureParams);
		$this->addHeader('Signature-Input', 'ocm=' . $paramsLine);
		$this->addHeader('Signature', 'ocm=:' . base64_encode($rawSignature) . ':');

		$this->setSigningElements([
			'label' => 'ocm',
			'components' => implode(' ', $this->headerList),
			'params' => $paramsLine,
			'signature' => $this->getSignature(),
		]);

		return $this;
	}

	/**
	 * @return array<string, string>
	 */
	private function headersByLowercaseName(): array {
		$out = [];
		foreach ($this->headers as $name => $value) {
			$out[strtolower($name)] = (string)$value;
		}
		return $out;
	}

	/**
	 * @throws SignatoryNotFoundException
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'host' => $this->host,
				'headers' => $this->headers,
				'algorithm' => $this->algorithm->value,
				'signingAlgorithm' => $this->signingAlgorithm,
				'method' => $this->method,
				'identity' => $this->identity,
				'uri' => $this->uri,
				'components' => $this->headerList,
				'signatureBase' => $this->signatureBaseString,
				'signatureParams' => $this->signatureParams,
			]
		);
	}
}
