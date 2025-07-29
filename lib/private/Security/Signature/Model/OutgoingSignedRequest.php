<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use NCU\Security\Signature\Enum\DigestAlgorithm;
use NCU\Security\Signature\Enum\SignatureAlgorithm;
use NCU\Security\Signature\Exceptions\SignatoryException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\IOutgoingSignedRequest;
use NCU\Security\Signature\ISignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use OC\Security\Signature\SignatureManager;

/**
 * extends ISignedRequest to add info requested at the generation of the signature
 *
 * @see ISignatureManager for details on signature
 * @since 31.0.0
 */
class OutgoingSignedRequest extends SignedRequest implements
	IOutgoingSignedRequest,
	JsonSerializable {
	private string $host = '';
	private array $headers = [];
	/** @var list<string> $headerList */
	private array $headerList = [];
	private SignatureAlgorithm $algorithm;
	public function __construct(
		string $body,
		ISignatoryManager $signatoryManager,
		private readonly string $identity,
		private readonly string $method,
		private readonly string $path,
	) {
		parent::__construct($body);

		$options = $signatoryManager->getOptions();
		$this->setHost($identity)
			->setAlgorithm($options['algorithm'] ?? SignatureAlgorithm::RSA_SHA256)
			->setSignatory($signatoryManager->getLocalSignatory())
			->setDigestAlgorithm($options['digestAlgorithm'] ?? DigestAlgorithm::SHA256);

		$headers = array_merge([
			'(request-target)' => strtolower($method) . ' ' . $path,
			'content-length' => strlen($this->getBody()),
			'date' => gmdate($options['dateHeader'] ?? SignatureManager::DATE_HEADER),
			'digest' => $this->getDigest(),
			'host' => $this->getHost()
		], $options['extraSignatureHeaders'] ?? []);

		$signing = $headerList = [];
		foreach ($headers as $element => $value) {
			$signing[] = $element . ': ' . $value;
			$headerList[] = $element;
			if ($element !== '(request-target)') {
				$this->addHeader($element, $value);
			}
		}

		$this->setHeaderList($headerList)
			->setSignatureData($signing);
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $host
	 * @return $this
	 * @since 31.0.0
	 */
	public function setHost(string $host): self {
		$this->host = $host;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getHost(): string {
		return $this->host;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key
	 * @param string|int|float $value
	 *
	 * @return self
	 * @since 31.0.0
	 */
	public function addHeader(string $key, string|int|float $value): self {
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * set the ordered list of used headers in the Signature
	 *
	 * @param list<string> $list
	 *
	 * @return self
	 * @since 31.0.0
	 */
	public function setHeaderList(array $list): self {
		$this->headerList = $list;
		return $this;
	}

	/**
	 * returns ordered list of used headers in the Signature
	 *
	 * @return list<string>
	 * @since 31.0.0
	 */
	public function getHeaderList(): array {
		return $this->headerList;
	}

	/**
	 * @inheritDoc
	 *
	 * @param SignatureAlgorithm $algorithm
	 *
	 * @return self
	 * @since 31.0.0
	 */
	public function setAlgorithm(SignatureAlgorithm $algorithm): self {
		$this->algorithm = $algorithm;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return SignatureAlgorithm
	 * @since 31.0.0
	 */
	public function getAlgorithm(): SignatureAlgorithm {
		return $this->algorithm;
	}

	/**
	 * @inheritDoc
	 *
	 * @return self
	 * @throws SignatoryException
	 * @throws SignatoryNotFoundException
	 * @since 31.0.0
	 */
	public function sign(): self {
		$privateKey = $this->getSignatory()->getPrivateKey();
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		openssl_sign(
			implode("\n", $this->getSignatureData()),
			$signed,
			$privateKey,
			$this->getAlgorithm()->value
		);

		$this->setSignature(base64_encode($signed));
		$this->setSigningElements(
			[
				'keyId="' . $this->getSignatory()->getKeyId() . '"',
				'algorithm="' . $this->getAlgorithm()->value . '"',
				'headers="' . implode(' ', $this->getHeaderList()) . '"',
				'signature="' . $this->getSignature() . '"'
			]
		);
		$this->addHeader('Signature', implode(',', $this->getSigningElements()));

		return $this;
	}

	/**
	 * @param string $clear
	 * @param string $privateKey
	 * @param SignatureAlgorithm $algorithm
	 *
	 * @return string
	 * @throws SignatoryException
	 */
	private function signString(string $clear, string $privateKey, SignatureAlgorithm $algorithm): string {
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		openssl_sign($clear, $signed, $privateKey, $algorithm->value);

		return base64_encode($signed);
	}

	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'host' => $this->host,
				'headers' => $this->headers,
				'algorithm' => $this->algorithm->value,
				'method' => $this->method,
				'identity' => $this->identity,
				'path' => $this->path,
			]
		);
	}
}
