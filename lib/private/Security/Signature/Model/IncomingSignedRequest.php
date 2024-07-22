<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use OCP\IRequest;
use OCP\Security\Signature\Exceptions\IncomingRequestNotFoundException;
use OCP\Security\Signature\Exceptions\SignatoryException;
use OCP\Security\Signature\Model\IIncomingSignedRequest;
use OCP\Security\Signature\Model\ISignatory;

/**
 * @inheritDoc
 *
 * @see ISignatureManager for details on signature
 * @since 30.0.0
 */
class IncomingSignedRequest extends SignedRequest
	implements
	IIncomingSignedRequest,
	JsonSerializable
{
	private ?IRequest $request = null;
	private int $time = 0;
	private string $origin = '';
	private string $estimatedSignature = '';

	/**
	 * @inheritDoc
	 *
	 * @param ISignatory $signatory
	 *
	 * @return $this
	 * @throws SignatoryException
	 * @since 30.0.0
	 */
	public function setSignatory(ISignatory $signatory): self {
		if (parse_url($signatory->getKeyId(), PHP_URL_HOST) !== $this->getOrigin()) {
			throw new SignatoryException('keyId from provider is different from the one from signed request');
		}

		parent::setSignatory($signatory);
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @param IRequest $request
	 * @return IIncomingSignedRequest
	 * @since 30.0.0
	 */
	public function setRequest(IRequest $request): IIncomingSignedRequest {
		$this->request = $request;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return IRequest
	 * @throws IncomingRequestNotFoundException
	 * @since 30.0.0
	 */
	public function getRequest(): IRequest {
		if ($this->request === null) {
			throw new IncomingRequestNotFoundException();
		}
		return $this->request;
	}

	/**
	 * @inheritDoc
	 *
	 * @param int $time
	 * @return IIncomingSignedRequest
	 * @since 30.0.0
	 */
	public function setTime(int $time): IIncomingSignedRequest {
		$this->time = $time;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return int
	 * @since 30.0.0
	 */
	public function getTime(): int {
		return $this->time;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $origin
	 * @return IIncomingSignedRequest
	 * @since 30.0.0
	 */
	public function setOrigin(string $origin): IIncomingSignedRequest {
		$this->origin = $origin;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getOrigin(): string {
		return $this->origin;
	}

	/**
	 * returns the keyId extracted from the signature headers.
	 * keyId is a mandatory entry in the headers of a signed request.
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getKeyId(): string {
		return $this->getSignatureHeader()['keyId'] ?? '';
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $signature
	 * @return IIncomingSignedRequest
	 * @since 30.0.0
	 */
	public function setEstimatedSignature(string $signature): IIncomingSignedRequest {
		$this->estimatedSignature = $signature;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getEstimatedSignature(): string {
		return $this->estimatedSignature;
	}

	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'body' => $this->getBody(),
				'time' => $this->getTime(),
				'incomingRequest' => $this->request ?? false,
				'origin' => $this->getOrigin(),
				'keyId' => $this->getKeyId(),
				'estimatedSignature' => $this->getEstimatedSignature(),
			]
		);
	}
}
