<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
