<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use NCU\Security\Signature\ISignatoryManager;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Model\IOutgoingSignedRequest;
use NCU\Security\Signature\SignatureAlgorithm;
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
			->setAlgorithm(SignatureAlgorithm::from($options['algorithm'] ?? 'sha256'))
			->setSignatory($signatoryManager->getLocalSignatory());

		$headers = array_merge([
			'(request-target)' => strtolower($method) . ' ' . $path,
			'content-length' => strlen($this->getBody()),
			'date' => gmdate($options['dateHeader'] ?? SignatureManager::DATE_HEADER),
			'digest' => $this->getDigest(),
			'host' => $this->getHost()
		], $options['extraSignatureHeaders'] ?? []);

		$signing = $headerList = [];
		foreach ($headers as $element => $value) {
			$value = $headers[$element];
			$signing[] = $element . ': ' . $value;
			$headerList[] = $element;
			if ($element !== '(request-target)') {
				$this->addHeader($element, $value);
			}
		}

		$this->setHeaderList($headerList)
			->setClearSignature(implode("\n", $signing));
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $host
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function setHost(string $host): IOutgoingSignedRequest {
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
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function addHeader(string $key, string|int|float $value): IOutgoingSignedRequest {
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
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function setHeaderList(array $list): IOutgoingSignedRequest {
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
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function setAlgorithm(SignatureAlgorithm $algorithm): IOutgoingSignedRequest {
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
