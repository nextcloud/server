<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;

class OCMDiscoveryHandler implements IHandler {
	public function __construct(
		private readonly OCMDiscoveryService $discoveryService,
	) {
	}

	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'ocm') {
			return $previousResponse;
		}

		return new GenericResponse(new JsonResponse($this->discoveryService->getLocalOCMProvider()));
	}
}
