<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\OCM\Exceptions\OCMArgumentException;

/**
 * Resolve the signing identity for a generic OCM endpoint before verification.
 *
 * Request parameters exposed by this event are unverified. Listeners must only
 * select an identity; endpoint processing belongs in {@see OCMEndpointRequestEvent}.
 *
 * @since 36.0.0
 */
#[Listenable(since: '36.0.0')]
final class OCMEndpointRequestAuthenticationEvent extends Event {
	private string $capability = '';
	private string $path;
	/** @var array<string, true> */
	private array $ocmAddresses = [];

	/**
	 * @param array<string, mixed> $params unverified request parameters
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly string $method,
		string $path,
		private readonly array $params,
		?string $ocmAddress,
	) {
		parent::__construct();
		if ($ocmAddress !== null) {
			$this->ocmAddresses[$ocmAddress] = true;
		}

		$path = trim($path, '/');
		if (!str_contains($path, '/')) {
			$this->capability = $path;
			$path = '';
		} else {
			[$this->capability, $path] = explode('/', $path, 2);
		}
		$this->path = $path;
	}

	/**
	 * @since 36.0.0
	 */
	public function getRequestedCapability(): string {
		return $this->capability;
	}

	/**
	 * @since 36.0.0
	 */
	public function getUsedMethod(): string {
		return $this->method;
	}

	/**
	 * @since 36.0.0
	 */
	public function getPath(): string {
		return '/' . $this->path;
	}

	/**
	 * @return array<string, mixed>
	 * @since 36.0.0
	 */
	public function getParams(): array {
		return $this->params;
	}

	/**
	 * Add a full OCM address that can identify the request signer.
	 *
	 * @since 36.0.0
	 */
	public function addOcmAddress(string $ocmAddress): void {
		if ($ocmAddress === '') {
			throw new \InvalidArgumentException(
				'The signer OCM address must not be empty',
			);
		}
		$this->ocmAddresses[$ocmAddress] = true;
	}

	/**
	 * @throws OCMArgumentException if listeners selected conflicting identities
	 * @since 36.0.0
	 */
	public function getOcmAddress(): ?string {
		if (count($this->ocmAddresses) > 1) {
			throw new OCMArgumentException(
				'Conflicting OCM signer identities selected',
			);
		}

		return array_key_first($this->ocmAddresses);
	}
}
