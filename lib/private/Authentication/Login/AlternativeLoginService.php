<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Login;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Authentication\IAlternativeLogin;
use OCP\Authentication\IAlternativeLoginProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class AlternativeLoginService {
	public function __construct(
		private readonly Coordinator $coordinator,
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
	) {

	}

	/**
	 * @return list<IAlternativeLogin>
	 */
	public function getAlternativeLogins(): array {
		$result = [];

		foreach ($this->coordinator->getRegistrationContext()->getAlternativeLoginProviders() as $registration) {
			if (!is_a($registration->getService(), IAlternativeLoginProvider::class, true)) {
				$this->logger->error('Alternative login option {option} does not implement {interface} and is therefore ignored.', [
					'option' => $registration->getService(),
					'interface' => IAlternativeLoginProvider::class,
					'app' => $registration->getAppId(),
				]);
				continue;
			}

			try {
				/** @var IAlternativeLoginProvider $provider */
				$provider = $this->container->get($registration->getService());
			} catch (ContainerExceptionInterface $e) {
				$this->logger->error('Alternative login option {option} can not be initialized.',
					[
						'exception' => $e,
						'option' => $registration->getService(),
						'app' => $registration->getAppId(),
					]);
				continue;
			}

			foreach ($provider->getAlternativeLogins() as $alternativeLogin) {
				try {
					$alternativeLogin->load();

					$result[] = $alternativeLogin;
				} catch (Throwable $e) {
					$this->logger->error('Alternative login option {option} had an error while loading.',
						[
							'exception' => $e,
							'option' => $registration->getService(),
							'app' => $registration->getAppId(),
						]);
				}
			}
		}

		foreach ($this->coordinator->getRegistrationContext()->getAlternativeLogins() as $registration) {
			if (!is_a($registration->getService(), IAlternativeLoginProvider::class, true)) {
				$this->logger->error('Alternative login option {option} does not implement {interface} and is therefore ignored.', [
					'option' => $registration->getService(),
					'interface' => IAlternativeLogin::class,
					'app' => $registration->getAppId(),
				]);
				continue;
			}

			try {
				/** @var IAlternativeLogin $provider */
				$provider = $this->container->get($registration->getService());
			} catch (ContainerExceptionInterface $e) {
				$this->logger->error('Alternative login option {option} can not be initialized.',
					[
						'exception' => $e,
						'option' => $registration->getService(),
						'app' => $registration->getAppId(),
					]);
			}

			try {
				$provider->load();

				$result[] = $provider;
			} catch (Throwable $e) {
				$this->logger->error('Alternative login option {option} had an error while loading.',
					[
						'exception' => $e,
						'option' => $registration->getService(),
						'app' => $registration->getAppId(),
					]);
			}
		}

		return $result;
	}
}
