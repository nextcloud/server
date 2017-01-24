<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\Providers\EMailProvider;
use OCP\AppFramework\QueryException;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\ILogger;
use OCP\IServerContainer;

class ActionProviderStore {

	/** @var IServerContainer */
	private $serverContainer;

	/** @var ILogger */
	private $logger;

	/**
	 * @param IServerContainer $serverContainer
	 */
	public function __construct(IServerContainer $serverContainer, ILogger $logger) {
		$this->serverContainer = $serverContainer;
		$this->logger = $logger;
	}

	/**
	 * @return IProvider[]
	 * @throws Exception
	 */
	public function getProviders() {
		// TODO: include apps
		$providerClasses = $this->getServerProviderClasses();
		$providers = [];

		foreach ($providerClasses as $class) {
			try {
				$providers[] = $this->serverContainer->query($class);
			} catch (QueryException $ex) {
				$this->logger->logException($ex, [
					'message' => "Could not load contacts menu action provider $class",
					'app' => 'core',
				]);
				throw new \Exception("Could not load contacts menu action provider");
			}
		}

		return $providers;
	}

	/**
	 * @return string[]
	 */
	private function getServerProviderClasses() {
		return [
			EMailProvider::class,
		];
	}

}
