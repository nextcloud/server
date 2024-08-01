<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Bruteforce;

use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\Capabilities\IPublicCapability;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;

class Capabilities implements IPublicCapability, IInitialStateExcludedCapability {
	public function __construct(
		private IRequest $request,
		private IThrottler $throttler,
	) {
	}

	/**
	 * @return array{bruteforce: array{delay: int, allow-listed: bool}}
	 */
	public function getCapabilities(): array {
		return [
			'bruteforce' => [
				'delay' => $this->throttler->getDelay($this->request->getRemoteAddress()),
				'allow-listed' => $this->throttler->isBypassListed($this->request->getRemoteAddress()),
			],
		];
	}
}
