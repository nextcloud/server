<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\Federation\ICloudFederationShare;

/**
 * Allows apps to prepare an outgoing share before it is signed and sent.
 *
 * The local grant and its shared secret already exist for federated file
 * shares. Listeners can use the provider ID to retrieve persisted share
 * attributes and add protocols using ICloudFederationShare::setProtocol().
 * They must negotiate remote support and respect the grant's permissions.
 * Throwing an exception aborts delivery without falling back to legacy sharing.
 *
 * @since 36.0.0
 */
#[Listenable(since: '36.0.0')]
final class BeforeOCMShareSentEvent extends Event {
	private ?string $shareType = null;
	private ?array $encryption = null;

	/** @since 36.0.0 */
	public function __construct(
		private readonly ICloudFederationShare $share,
	) {
		parent::__construct();
	}

	/** @since 36.0.0 */
	public function getShare(): ICloudFederationShare {
		return $this->share;
	}

	/**
	 * Override the recipient type in the outgoing payload without changing the local share.
	 *
	 * @since 36.0.0
	 */
	public function setShareType(string $shareType): void {
		$this->shareType = $shareType;
	}

	/**
	 * Attach encryption metadata to the outgoing share. This does not encrypt the resource.
	 *
	 * @param array<string, mixed> $encryption Metadata for the negotiated encryption scheme
	 * @since 36.0.0
	 */
	public function setEncryption(array $encryption): void {
		$this->encryption = $encryption;
	}

	/**
	 * Return the outgoing payload, including any recipient type override and encryption metadata.
	 *
	 * @since 36.0.0
	 */
	public function getPayload(): array {
		$payload = $this->share->getShare();
		if ($this->shareType !== null) {
			$payload['shareType'] = $this->shareType;
		}
		if ($this->encryption !== null) {
			$payload['encryption'] = $this->encryption;
		}
		return $payload;
	}
}
