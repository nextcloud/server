<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\AppFramework\Http\JSONResponse;
use OCP\EventDispatcher\Event;
use OCP\Security\Signature\IIncomingSignedRequest;

/**
 * An incoming POST /ocm/shares request before it is passed to the resource provider.
 *
 * Listeners that handle a share must authorize it before returning a response.
 * Without a response, the request continues to the built-in handling.
 *
 * @since 36.0.0
 */
#[Listenable(since: '36.0.0')]
final class OCMShareRequestEvent extends Event {
	private ?JSONResponse $response = null;

	/**
	 * @param array<string, mixed> $payload Share request fields, including the full recipient cloud ID
	 * @since 36.0.0
	 */
	public function __construct(
		private readonly array $payload,
		private readonly ?IIncomingSignedRequest $signedRequest,
	) {
		parent::__construct();
	}

	/**
	 * @return array<string, mixed>
	 * @since 36.0.0
	 */
	public function getPayload(): array {
		return $this->payload;
	}

	/**
	 * Return the verified request, or null if no signature was verified.
	 *
	 * @since 36.0.0
	 */
	public function getSignedRequest(): ?IIncomingSignedRequest {
		return $this->signedRequest;
	}

	/**
	 * Claim the request and stop dispatch to other handlers. Return HTTP 201 if the share was created.
	 *
	 * @since 36.0.0
	 */
	public function setResponse(JSONResponse $response): void {
		$this->response = $response;
		$this->stopPropagation();
	}

	/** @since 36.0.0 */
	public function getResponse(): ?JSONResponse {
		return $this->response;
	}
}
