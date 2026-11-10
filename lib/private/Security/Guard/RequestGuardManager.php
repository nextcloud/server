<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Guard;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\IRequest;
use OCP\Security\Guard\GuardDecision;
use OCP\Security\Guard\IRequestGuard;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class RequestGuardManager {
	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Asks the registered guards in order and returns the first refusal.
	 */
	public function check(IRequest $request): ?GuardDecision {
		$registrations = $this->coordinator->getRegistrationContext()?->getRequestGuards() ?? [];
		foreach ($registrations as $registration) {
			$class = $registration->getService();
			try {
				/** @var IRequestGuard $guard */
				$guard = $this->container->get($class);
				$decision = $guard->check($request);
			} catch (\Throwable $t) {
				$this->logger->error('Exception in request guard ' . $class . ': ' . $t->getMessage(), [
					'app' => $registration->getAppId(),
					'exception' => $t,
				]);
				continue;
			}

			if ($decision !== null) {
				return $decision;
			}
		}

		return null;
	}
}
