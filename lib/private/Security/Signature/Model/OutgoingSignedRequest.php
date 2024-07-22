<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use OCP\Security\Signature\Model\IOutgoingSignedRequest;

class OutgoingSignedRequest extends SignedRequest
	implements
	IOutgoingSignedRequest,
	JsonSerializable
{
	private string $host = '';
	private array $headers = [];
	private string $clearSignature = '';
	private string $algorithm;

	/**
	 * @inheritDoc
	 *
	 * @param string $host
	 * @return IOutgoingSignedRequest
	 * @since 30.0.0
	 */
	public function setHost(string $host): self {
		$this->host = $host;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getHost(): string {
		return $this->host;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $key
	 * @param string|int|float|bool|array $value
	 *
	 * @return IOutgoingSignedRequest
	 * @since 30.0.0
	 */
	public function addHeader(string $key, string|int|float|bool|array $value): self {
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 30.0.0
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $estimated
	 *
	 * @return IOutgoingSignedRequest
	 * @since 30.0.0
	 */
	public function setClearSignature(string $estimated): self {
		$this->clearSignature = $estimated;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getClearSignature(): string {
		return $this->clearSignature;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $algorithm
	 *
	 * @return IOutgoingSignedRequest
	 * @since 30.0.0
	 */
	public function setAlgorithm(string $algorithm): self {
		$this->algorithm = $algorithm;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getAlgorithm(): string {
		return $this->algorithm;
	}

	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'headers' => $this->headers,
				'host' => $this->getHost(),
				'clearSignature' => $this->getClearSignature(),
			]
		);
	}
}
