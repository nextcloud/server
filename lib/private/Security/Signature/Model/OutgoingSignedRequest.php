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

	/** remote address */
	public function setHost(string $host): IOutgoingSignedRequest {
		$this->host = $host;
		return $this;
	}

	public function getHost(): string {
		return $this->host;
	}

	public function addHeader(string $key, string|int|float|bool|array $value): IOutgoingSignedRequest {
		$this->headers[$key] = $value;
		return $this;
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public function setClearSignature(string $estimated): self {
		$this->clearSignature = $estimated;
		return $this;
	}

	public function getClearSignature(): string {
		return $this->clearSignature;
	}

	public function setAlgorithm(string $algorithm): IOutgoingSignedRequest {
		$this->algorithm = $algorithm;
		return $this;
	}

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
