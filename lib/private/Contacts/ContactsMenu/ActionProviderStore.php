<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Contacts\ContactsMenu;

use Exception;
use OC\App\AppManager;
use OC\Contacts\ContactsMenu\Providers\EMailProvider;
use OC\Contacts\ContactsMenu\Providers\LocalTimeProvider;
use OC\Contacts\ContactsMenu\Providers\ProfileProvider;
use OCP\AppFramework\QueryException;
use OCP\Contacts\ContactsMenu\IBulkProvider;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IServerContainer;
use OCP\IUser;
use Psr\Log\LoggerInterface;

class ActionProviderStore {
	public function __construct(
		private IServerContainer $serverContainer,
		private AppManager $appManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return list<IProvider|IBulkProvider>
	 * @throws Exception
	 */
	public function getProviders(IUser $user): array {
		$appClasses = $this->getAppProviderClasses($user);
		$providerClasses = $this->getServerProviderClasses();
		$allClasses = array_merge($providerClasses, $appClasses);
		/** @var list<IProvider|IBulkProvider> $providers */
		$providers = [];

		foreach ($allClasses as $class) {
			try {
				$provider = $this->serverContainer->get($class);
				if ($provider instanceof IProvider || $provider instanceof IBulkProvider) {
					$providers[] = $provider;
				} else {
					$this->logger->warning('Ignoring invalid contacts menu provider', [
						'class' => $class,
					]);
				}
			} catch (QueryException $ex) {
				$this->logger->error(
					'Could not load contacts menu action provider ' . $class,
					[
						'app' => 'core',
						'exception' => $ex,
					]
				);
				throw new Exception('Could not load contacts menu action provider');
			}
		}

		return $providers;
	}

	/**
	 * @return string[]
	 */
	private function getServerProviderClasses(): array {
		return [
			ProfileProvider::class,
			LocalTimeProvider::class,
			EMailProvider::class,
		];
	}

	/**
	 * @return string[]
	 */
	private function getAppProviderClasses(IUser $user): array {
		return array_reduce($this->appManager->getEnabledAppsForUser($user), function ($all, $appId) {
			$info = $this->appManager->getAppInfo($appId);

			if (!isset($info['contactsmenu'])) {
				// Nothing to add
				return $all;
			}

			$providers = array_reduce($info['contactsmenu'], function ($all, $provider) {
				return array_merge($all, [$provider]);
			}, []);

			return array_merge($all, $providers);
		}, []);
	}
}
