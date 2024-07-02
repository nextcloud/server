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

class IncomingSignedRequest extends SignedRequest
	implements
	IIncomingSignedRequest,
	JsonSerializable
{
	private ?IRequest $request = null;
	private int $time = 0;
	private string $origin = '';
	private string $host = '';
	private string $estimatedSignature = '';

	/**
	 * @param ISignatory $signatory
	 *
	 * @return $this
	 * @throws SignatoryException
	 */
	public function setSignatory(ISignatory $signatory): self {
		if (parse_url($signatory->getKeyId(), PHP_URL_HOST) !== $this->getOrigin()) {
			throw new SignatoryException('keyId from provider is different from the one from signed request');
		}

		parent::setSignatory($signatory);
		return $this;
	}

	public function setRequest(IRequest $request): IIncomingSignedRequest {
		$this->request = $request;
		return $this;
	}

	public function getRequest(): IRequest {
		if ($this->request === null) {
			throw new IncomingRequestNotFoundException();
		}
		return $this->request;
	}

	public function setTime(int $time): IIncomingSignedRequest {
		$this->time = $time;
		return $this;
	}

	public function getTime(): int {
		return $this->time;
	}

	public function setOrigin(string $origin): IIncomingSignedRequest {
		$this->origin = $origin;
		return $this;
	}

	public function getOrigin(): string {
		return $this->origin;
	}

	/** local address */
	public function setHost(string $host): IIncomingSignedRequest {
		$this->host = $host;
		return $this;
	}

	public function getHost(): string {
		return $this->host;
	}

	public function setEstimatedSignature(string $signature): IIncomingSignedRequest {
		$this->estimatedSignature = $signature;
		return $this;
	}

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
				'host' => $this->getHost(),
				'estimatedSignature' => $this->getEstimatedSignature(),
			]
		);
	}
}
