<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Signature\Model;

use JsonSerializable;
use OCP\Security\PublicPrivateKeyPairs\Model\IKeyPair;
use OCP\Security\Signature\Exceptions\SignatoryException;
use OCP\Security\Signature\Model\ISignatory;
use OCP\Security\Signature\Model\ISignedRequest;

class SignedRequest implements ISignedRequest, JsonSerializable {
	private string $digest;
	private string $signedSignature = '';
	private array $signatureHeader = [];
	private ?ISignatory $signatory = null;

	public function __construct(
		private readonly string $body) {
		$this->digest = 'SHA-256=' . base64_encode(hash("sha256", utf8_encode($body), true));
	}

	public function getBody(): string {
		return $this->body;
	}

	public function getDigest(): string {
		return $this->digest;
	}

	public function getSignatureHeader(): array {
		return $this->signatureHeader;
	}

	public function setSignatureHeader(array $signatureHeader): self {
		$this->signatureHeader = $signatureHeader;
		return $this;
	}

	public function getKeyId(): string {
		return $this->getSignatureHeader()['keyId'] ?? '';
	}

	public function setSignedSignature(string $signedSignature): ISignedRequest {
		$this->signedSignature = $signedSignature;
		return $this;
	}

	public function getSignedSignature(): string {
		return $this->signedSignature;
	}

	/**
	 * @param ISignatory $signatory
	 *
	 * @return ISignedRequest
	 */
	public function setSignatory(ISignatory $signatory): ISignedRequest {
		$this->signatory = $signatory;
		return $this;
	}

	public function getSignatory(): ISignatory {
		return $this->signatory;
	}

	public function hasSignatory(): bool {
		return ($this->signatory !== null);
	}

	public function jsonSerialize(): array {
		return [
			'body' => $this->getBody(),
			'signatureHeader' => $this->getSignatureHeader(),
			'signedSignature' => $this->getSignedSignature(),
			'keyId' => $this->getKeyId(),
			'signatory' => $this->signatory ?? false,
		];
	}
}
