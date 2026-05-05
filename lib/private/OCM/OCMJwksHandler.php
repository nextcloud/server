<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/** Serves `/.well-known/jwks.json` (RFC 7517) for the RFC 9421 keys. */
class OCMJwksHandler implements IHandler {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly OCMSignatoryManager $signatoryManager,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'jwks.json') {
			return $previousResponse;
		}

		$keys = [];
		if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
			try {
				foreach ($this->signatoryManager->getLocalEd25519Jwks() as $jwk) {
					$keys[] = $jwk;
				}
			} catch (Throwable $e) {
				$this->logger->warning('failed to build local Ed25519 JWKs', ['exception' => $e]);
			}
		}

		return new GenericResponse(new JSONResponse(['keys' => $keys]));
	}
}
