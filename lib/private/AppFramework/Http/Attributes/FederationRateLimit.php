<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Http\Attributes;

use Attribute;
use OC\OCM\OCMDiscoveryService;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\IRequest;
use OCP\Server;

/**
 * Attribute for controller methods that want to limit the times a not logged-in
 * guest can call the endpoint in a given time period.
 *
 * Unlike regular AnonRateLimit, signed requests from trusted servers are excluded from the rate limit.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class FederationRateLimit extends AnonRateLimit {
	private readonly OCMDiscoveryService $discoveryService;
	private readonly ?TrustedServers $trustedServers;

	public function __construct(int $limit, int $period) {
		parent::__construct($limit, $period);

		$this->discoveryService = Server::get(OCMDiscoveryService::class);
		$this->trustedServers = Server::get(TrustedServers::class);
	}

	#[\Override]
	public function shouldApply(IRequest $request): bool {
		if ($this->trustedServers === null) {
			return true;
		}

		try {
			$signedRequest = $this->discoveryService->getIncomingSignedRequest();
			if (!$signedRequest) {
				return true;
			}
			$signedRequest->verify();
			return !$this->trustedServers->isTrustedServer($signedRequest->getOrigin());
		} catch (\Exception) {
			// no or invalid signature
			return true;
		}
	}
}
