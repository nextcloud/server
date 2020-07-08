<?php

declare(strict_types=1);

/**
 *
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Support\Subscription;

use OCP\AppFramework\QueryException;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\Support\Subscription\Exception\AlreadyRegisteredException;
use OCP\Support\Subscription\IRegistry;
use OCP\Support\Subscription\ISubscription;
use OCP\Support\Subscription\ISupportedApps;

class Registry implements IRegistry {

	/** @var ISubscription */
	private $subscription = null;

	/** @var string */
	private $subscriptionService = null;

	/** @var IConfig */
	private $config;

	/** @var IServerContainer */
	private $container;

	public function __construct(IConfig $config, IServerContainer $container) {
		$this->config = $config;
		$this->container = $container;
	}

	private function getSubscription(): ?ISubscription {
		if ($this->subscription === null && $this->subscriptionService !== null) {
			try {
				$this->subscription = $this->container->query($this->subscriptionService);
			} catch (QueryException $e) {
				// Ignore this
			}
		}

		return $this->subscription;
	}

	/**
	 * Register a subscription instance. In case it is called multiple times the
	 * first one is used.
	 *
	 * @param ISubscription $subscription
	 * @throws AlreadyRegisteredException
	 *
	 * @since 17.0.0
	 */
	public function register(ISubscription $subscription): void {
		if ($this->subscription !== null || $this->subscriptionService !== null) {
			throw new AlreadyRegisteredException();
		}
		$this->subscription = $subscription;
	}

	public function registerService(string $subscriptionService): void {
		if ($this->subscription !== null || $this->subscriptionService !== null) {
			throw new AlreadyRegisteredException();
		}

		$this->subscriptionService = $subscriptionService;
	}


	/**
	 * Fetches the list of app IDs that are supported by the subscription
	 *
	 * @since 17.0.0
	 */
	public function delegateGetSupportedApps(): array {
		if ($this->getSubscription() instanceof ISupportedApps) {
			return $this->getSubscription()->getSupportedApps();
		}
		return [];
	}

	/**
	 * Indicates if a valid subscription is available
	 *
	 * @since 17.0.0
	 */
	public function delegateHasValidSubscription(): bool {
		// Allow overwriting this manually for environments where the subscription information cannot be fetched
		if ($this->config->getSystemValueBool('has_valid_subscription')) {
			return true;
		}

		if ($this->getSubscription() instanceof ISubscription) {
			return $this->getSubscription()->hasValidSubscription();
		}
		return false;
	}

	/**
	 * Indicates if the subscription has extended support
	 *
	 * @since 17.0.0
	 */
	public function delegateHasExtendedSupport(): bool {
		if ($this->getSubscription() instanceof ISubscription && method_exists($this->subscription, 'hasExtendedSupport')) {
			return $this->getSubscription()->hasExtendedSupport();
		}
		return false;
	}
}
