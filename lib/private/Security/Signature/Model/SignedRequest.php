<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;
use NCU\Security\Signature\Model\ISignatory;
use NCU\Security\Signature\Model\ISignedRequest;

/**
 * @inheritDoc
 *
 * @since 31.0.0
 */
class SignedRequest implements ISignedRequest, JsonSerializable {
	private string $digest;
	private array $signatureElements = [];
	private string $clearSignature = '';
	private string $signedSignature = '';
	private ?ISignatory $signatory = null;

	public function __construct(
		private readonly string $body,
	) {
		// digest is created on the fly using $body
		$this->digest = 'SHA-256=' . base64_encode(hash('sha256', utf8_encode($body), true));
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getBody(): string {
		return $this->body;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getDigest(): string {
		return $this->digest;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $elements
	 *
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setSignatureElements(array $elements): ISignedRequest {
		$this->signatureElements = $elements;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getSignatureElements(): array {
		return $this->signatureElements;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @since 31.0.0
	 *
	 */
	public function getSignatureElement(string $key): string {
		if (!array_key_exists($key, $this->signatureElements)) {
			throw new SignatureElementNotFoundException('missing element ' . $key . ' in Signature header');
		}

		return $this->signatureElements[$key];
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $clearSignature
	 *
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setClearSignature(string $clearSignature): ISignedRequest {
		$this->clearSignature = $clearSignature;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getClearSignature(): string {
		return $this->clearSignature;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $signedSignature
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setSignedSignature(string $signedSignature): ISignedRequest {
		$this->signedSignature = $signedSignature;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getSignedSignature(): string {
		return $this->signedSignature;
	}

	/**
	 * @inheritDoc
	 *
	 * @param ISignatory $signatory
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setSignatory(ISignatory $signatory): ISignedRequest {
		$this->signatory = $signatory;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return ISignatory
	 * @throws SignatoryNotFoundException
	 * @since 31.0.0
	 */
	public function getSignatory(): ISignatory {
		if ($this->signatory === null) {
			throw new SignatoryNotFoundException();
		}

		return $this->signatory;
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool
	 * @since 31.0.0
	 */
	public function hasSignatory(): bool {
		return ($this->signatory !== null);
	}

	public function jsonSerialize(): array {
		return [
			'body' => $this->body,
			'digest' => $this->digest,
			'signatureElements' => $this->signatureElements,
			'clearSignature' => $this->clearSignature,
			'signedSignature' => $this->signedSignature,
			'signatory' => $this->signatory ?? false,
		];
	}
}
