<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OCA\User_LDAP\Jobs;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\User;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;

class Sync extends TimedJob {
	public const MAX_INTERVAL = 12 * 60 * 60; // 12h
	public const MIN_INTERVAL = 30 * 60; // 30min

	protected Helper $ldapHelper;
	protected LDAP $ldap;
	protected Manager $userManager;
	protected UserMapping $mapper;
	protected IConfig $config;
	protected IAvatarManager $avatarManager;
	protected IDBConnection $dbc;
	protected IUserManager $ncUserManager;
	protected LoggerInterface $logger;
	protected IManager $notificationManager;
	protected ConnectionFactory $connectionFactory;
	protected AccessFactory $accessFactory;
	protected IEventDispatcher $dispatcher;

	public function __construct(
		Manager $userManager,
		IEventDispatcher $dispatcher,
		IConfig $config,
		ITimeFactory $timeFactory,
		IDBConnection $dbConnection,
		IAvatarManager $avatarManager,
		IUserManager $ncUserManager,
		LoggerInterface $logger,
		IManager $notificationManager,
		UserMapping $userMapping,
		Helper $ldapHelper,
		ConnectionFactory $connectionFactory
	) {
		parent::__construct($timeFactory);
		$this->userManager = $userManager;
		$this->dispatcher = $dispatcher;
		$this->config = $config;
		$this->setInterval(
			(int)$config->getAppValue(
				'user_ldap',
				'background_sync_interval',
				self::MIN_INTERVAL
			)
		);

		$this->ldapHelper = $ldapHelper;
		$this->ldap = new LDAP($this->config->getSystemValueString('ldap_log_file'));
		$this->avatarManager = $avatarManager;
		$this->dbc = $dbConnection;
		$this->ncUserManager = $ncUserManager;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->notificationManager = $notificationManager;
		$this->mapper = $userMapping;
		$this->connectionFactory = $connectionFactory;

		$this->accessFactory = new AccessFactory(
			$this->ldap,
			$this->userManager,
			$this->ldapHelper,
			$this->config,
			$this->ncUserManager,
			$this->logger,
			$this->dispatcher
		);
	}

	/**
	 * Updates the interval
	 *
	 * The idea is to adjust the interval depending on the amount of known users
	 * and the attempt to update each user one day. At most it would run every
	 * 30 minutes, and at least every 12 hours.
	 */
	public function updateInterval(): void {
		$minPagingSize = $this->getMinPagingSize();
		$mappedUsers = $this->mapper->count();

		$runsPerDay = ($minPagingSize === 0 || $mappedUsers === 0) ? self::MAX_INTERVAL
			: $mappedUsers / $minPagingSize;
		$interval = floor(24 * 60 * 60 / $runsPerDay);
		$interval = min(max($interval, self::MIN_INTERVAL), self::MAX_INTERVAL);

		$this->config->setAppValue('user_ldap', 'background_sync_interval', $interval);
	}

	/**
	 * Returns the smallest configured paging size
	 */
	protected function getMinPagingSize(): int {
		$configKeys = $this->config->getAppKeys('user_ldap');
		$configKeys = array_filter($configKeys, function ($key) {
			return strpos($key, 'ldap_paging_size') !== false;
		});
		$minPagingSize = null;
		foreach ($configKeys as $configKey) {
			$pagingSize = $this->config->getAppValue('user_ldap', $configKey, $minPagingSize);
			$minPagingSize = $minPagingSize === null ? $pagingSize : min($minPagingSize, $pagingSize);
		}
		return (int)$minPagingSize;
	}

	/**
	 * @param array $argument
	 */
	public function run($argument) {
		$isBackgroundJobModeAjax = $this->config
				->getAppValue('core', 'backgroundjobs_mode', 'ajax') === 'ajax';
		if ($isBackgroundJobModeAjax) {
			return;
		}

		$cycleData = $this->getCycle();
		if ($cycleData === null) {
			$cycleData = $this->determineNextCycle();
			if ($cycleData === null) {
				$this->updateInterval();
				return;
			}
		}

		if (!$this->qualifiesToRun($cycleData)) {
			$this->updateInterval();
			return;
		}

		try {
			$expectMoreResults = $this->runCycle($cycleData);
			if ($expectMoreResults) {
				$this->increaseOffset($cycleData);
			} else {
				$this->determineNextCycle($cycleData);
			}
			$this->updateInterval();
		} catch (ServerNotAvailableException $e) {
			$this->determineNextCycle($cycleData);
		}
	}

	/**
	 * @param array{offset: int, prefix: string} $cycleData
	 * @return bool whether more results are expected from the same configuration
	 */
	public function runCycle(array $cycleData): bool {
		$connection = $this->connectionFactory->get($cycleData['prefix']);
		$access = $this->accessFactory->get($connection);
		$access->setUserMapper($this->mapper);

		$filter = $access->combineFilterWithAnd([
			$access->connection->ldapUserFilter,
			$access->connection->ldapUserDisplayName . '=*',
			$access->getFilterPartForUserSearch('')
		]);
		$results = $access->fetchListOfUsers(
			$filter,
			$access->userManager->getAttributes(),
			$connection->ldapPagingSize,
			$cycleData['offset'],
			true
		);

		if ((int)$connection->ldapPagingSize === 0) {
			return false;
		}
		return count($results) >= (int)$connection->ldapPagingSize;
	}

	/**
	 * Returns the info about the current cycle that should be run, if any,
	 * otherwise null
	 * @return ?array{offset: int, prefix: string}
	 */
	public function getCycle(): ?array {
		$prefixes = $this->ldapHelper->getServerConfigurationPrefixes(true);
		if (count($prefixes) === 0) {
			return null;
		}

		$cycleData = [
			'prefix' => $this->config->getAppValue('user_ldap', 'background_sync_prefix', 'none'),
			'offset' => (int)$this->config->getAppValue('user_ldap', 'background_sync_offset', '0'),
		];

		if (
			$cycleData['prefix'] !== 'none'
			&& in_array($cycleData['prefix'], $prefixes)
		) {
			return $cycleData;
		}

		return null;
	}

	/**
	 * Save the provided cycle information in the DB
	 *
	 * @param array{prefix: ?string, offset: int} $cycleData
	 */
	public function setCycle(array $cycleData): void {
		$this->config->setAppValue('user_ldap', 'background_sync_prefix', $cycleData['prefix']);
		$this->config->setAppValue('user_ldap', 'background_sync_offset', (string)$cycleData['offset']);
	}

	/**
	 * returns data about the next cycle that should run, if any, otherwise
	 * null. It also always goes for the next LDAP configuration!
	 *
	 * @param array|null $cycleData the old cycle
	 * @return ?array{prefix: string, offset: int}
	 */
	public function determineNextCycle(?array $cycleData = null): ?array {
		$prefixes = $this->ldapHelper->getServerConfigurationPrefixes(true);
		if (count($prefixes) === 0) {
			return null;
		}

		// get the next prefix in line and remember it
		$oldPrefix = $cycleData === null ? null : $cycleData['prefix'];
		$prefix = $this->getNextPrefix($oldPrefix);
		if ($prefix === null) {
			return null;
		}
		$cycleData['prefix'] = $prefix;
		$cycleData['offset'] = 0;
		$this->setCycle($cycleData);

		return $cycleData;
	}

	/**
	 * Checks whether the provided cycle should be run. Currently, only the
	 * last configuration change goes into account (at least one hour).
	 *
	 * @param $cycleData{prefix: string}
	 */
	public function qualifiesToRun(array $cycleData): bool {
		$lastChange = $this->config->getAppValue('user_ldap', $cycleData['prefix'] . '_lastChange', 0);
		if ((time() - $lastChange) > 60 * 30) {
			return true;
		}
		return false;
	}

	/**
	 * Increases the offset of the current cycle for the next run
	 *
	 * @param array{prefix: string, offset: int} $cycleData
	 */
	protected function increaseOffset(array $cycleData): void {
		$ldapConfig = new Configuration($cycleData['prefix']);
		$cycleData['offset'] += $ldapConfig->ldapPagingSize;
		$this->setCycle($cycleData);
	}

	/**
	 * Determines the next configuration prefix based on the last one (if any)
	 */
	protected function getNextPrefix(?string $lastPrefix): ?string {
		$prefixes = $this->ldapHelper->getServerConfigurationPrefixes(true);
		$noOfPrefixes = count($prefixes);
		if ($noOfPrefixes === 0) {
			return null;
		}
		$i = $lastPrefix === null ? false : array_search($lastPrefix, $prefixes, true);
		if ($i === false) {
			$i = -1;
		} else {
			$i++;
		}

		if (!isset($prefixes[$i])) {
			$i = 0;
		}
		return $prefixes[$i];
	}

	/**
	 * Only used in tests
	 */
	public function overwritePropertiesForTest(LDAP $ldapWrapper, AccessFactory $accessFactory): void {
		$this->ldap = $ldapWrapper;
		$this->accessFactory = $accessFactory;
	}
}
