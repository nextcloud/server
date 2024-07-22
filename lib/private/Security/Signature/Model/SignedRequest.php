<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use OCP\Security\Signature\Model\ISignatory;
use OCP\Security\Signature\Model\ISignedRequest;

/**
 * @inheritDoc
 *
 * @since 30.0.0
 */
class SignedRequest implements ISignedRequest, JsonSerializable {
	private string $digest;
	private string $signedSignature = '';
	private array $signatureHeader = [];
	private ?ISignatory $signatory = null;

	public function __construct(
		private readonly string $body
	) {
		// digest is created on the fly using $body
		$this->digest = 'SHA-256=' . base64_encode(hash('sha256', utf8_encode($body), true));
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getBody(): string {
		return $this->body;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getDigest(): string {
		return $this->digest;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $signatureHeader
	 * @return ISignedRequest
	 * @since 30.0.0
	 */
	public function setSignatureHeader(array $signatureHeader): self {
		$this->signatureHeader = $signatureHeader;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 30.0.0
	 */
	public function getSignatureHeader(): array {
		return $this->signatureHeader;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $signedSignature
	 * @return ISignedRequest
	 * @since 30.0.0
	 */
	public function setSignedSignature(string $signedSignature): ISignedRequest {
		$this->signedSignature = $signedSignature;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getSignedSignature(): string {
		return $this->signedSignature;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ISignatory $signatory
	 * @return ISignedRequest
	 * @since 30.0.0
	 */
	public function setSignatory(ISignatory $signatory): ISignedRequest {
		$this->signatory = $signatory;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return ISignatory
	 * @since 30.0.0
	 */
	public function getSignatory(): ISignatory {
		return $this->signatory;
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool
	 * @since 30.0.0
	 */
	public function hasSignatory(): bool {
		return ($this->signatory !== null);
	}

	public function jsonSerialize(): array {
		return [
			'body' => $this->getBody(),
			'signatureHeader' => $this->getSignatureHeader(),
			'signedSignature' => $this->getSignedSignature(),
			'signatory' => $this->signatory ?? false,
		];
	}
}
