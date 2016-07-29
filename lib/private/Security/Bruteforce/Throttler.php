<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OC\Security\Bruteforce;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;

/**
 * Class Throttler implements the bruteforce protection for security actions in
 * Nextcloud.
 *
 * It is working by logging invalid login attempts to the database and slowing
 * down all login attempts from the same subnet. The max delay is 30 seconds and
 * the starting delay are 200 milliseconds. (after the first failed login)
 *
 * This is based on Paragonie's AirBrake for Airship CMS. You can find the original
 * code at https://github.com/paragonie/airship/blob/7e5bad7e3c0fbbf324c11f963fd1f80e59762606/src/Engine/Security/AirBrake.php
 *
 * @package OC\Security\Bruteforce
 */
class Throttler {
	const LOGIN_ACTION = 'login';

	/** @var IDBConnection */
	private $db;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var ILogger */
	private $logger;
	/** @var IConfig */
	private $config;

	/**
	 * @param IDBConnection $db
	 * @param ITimeFactory $timeFactory
	 * @param ILogger $logger
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $db,
								ITimeFactory $timeFactory,
								ILogger $logger,
								IConfig $config) {
		$this->db = $db;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * Convert a number of seconds into the appropriate DateInterval
	 *
	 * @param int $expire
	 * @return \DateInterval
	 */
	private function getCutoff($expire) {
		$d1 = new \DateTime();
		$d2 = clone $d1;
		$d2->sub(new \DateInterval('PT' . $expire . 'S'));
		return $d2->diff($d1);
	}

	/**
	 * Return the given subnet for an IPv4 address and mask bits
	 *
	 * @param string $ip
	 * @param int $maskBits
	 * @return string
	 */
	private function getIPv4Subnet($ip,
								  $maskBits = 32) {
		$binary = \inet_pton($ip);
		for ($i = 32; $i > $maskBits; $i -= 8) {
			$j = \intdiv($i, 8) - 1;
			$k = (int) \min(8, $i - $maskBits);
			$mask = (0xff - ((pow(2, $k)) - 1));
			$int = \unpack('C', $binary[$j]);
			$binary[$j] = \pack('C', $int[1] & $mask);
		}
		return \inet_ntop($binary).'/'.$maskBits;
	}

	/**
	 * Return the given subnet for an IPv6 address and mask bits
	 *
	 * @param string $ip
	 * @param int $maskBits
	 * @return string
	 */
	private function getIPv6Subnet($ip, $maskBits = 48) {
		$binary = \inet_pton($ip);
		for ($i = 128; $i > $maskBits; $i -= 8) {
			$j = \intdiv($i, 8) - 1;
			$k = (int) \min(8, $i - $maskBits);
			$mask = (0xff - ((pow(2, $k)) - 1));
			$int = \unpack('C', $binary[$j]);
			$binary[$j] = \pack('C', $int[1] & $mask);
		}
		return \inet_ntop($binary).'/'.$maskBits;
	}

	/**
	 * Return the given subnet for an IP and the configured mask bits
	 *
	 * Determine if the IP is an IPv4 or IPv6 address, then pass to the correct
	 * method for handling that specific type.
	 *
	 * @param string $ip
	 * @return string
	 */
	private function getSubnet($ip) {
		if (\preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)) {
			return $this->getIPv4Subnet(
				$ip,
				32
			);
		}
		return $this->getIPv6Subnet(
			$ip,
			128
		);
	}

	/**
	 * Register a failed attempt to bruteforce a security control
	 *
	 * @param string $action
	 * @param string $ip
	 * @param array $metadata Optional metadata logged to the database
	 */
	public function registerAttempt($action,
									$ip,
									array $metadata = []) {
		// No need to log if the bruteforce protection is disabled
		if($this->config->getSystemValue('auth.bruteforce.protection.enabled', true) === false) {
			return;
		}

		$values = [
			'action' => $action,
			'occurred' => $this->timeFactory->getTime(),
			'ip' => $ip,
			'subnet' => $this->getSubnet($ip),
			'metadata' => json_encode($metadata),
		];

		$this->logger->notice(
			sprintf(
				'Bruteforce attempt from "%s" detected for action "%s".',
				$ip,
				$action
			),
			[
				'app' => 'core',
			]
		);

		$qb = $this->db->getQueryBuilder();
		$qb->insert('bruteforce_attempts');
		foreach($values as $column => $value) {
			$qb->setValue($column, $qb->createNamedParameter($value));
		}
		$qb->execute();
	}

	/**
	 * Get the throttling delay (in milliseconds)
	 *
	 * @param string $ip
	 * @return int
	 */
	public function getDelay($ip) {
		$cutoffTime = (new \DateTime())
			->sub($this->getCutoff(43200))
			->getTimestamp();

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('bruteforce_attempts')
			->where($qb->expr()->gt('occurred', $qb->createNamedParameter($cutoffTime)))
			->andWhere($qb->expr()->eq('subnet', $qb->createNamedParameter($this->getSubnet($ip))));
		$attempts = count($qb->execute()->fetchAll());

		if ($attempts === 0) {
			return 0;
		}

		$maxDelay = 30;
		$firstDelay = 0.1;
		if ($attempts > (8 * PHP_INT_SIZE - 1))  {
			// Don't ever overflow. Just assume the maxDelay time:s
			$firstDelay = $maxDelay;
		} else {
			$firstDelay *= pow(2, $attempts);
			if ($firstDelay > $maxDelay) {
				$firstDelay = $maxDelay;
			}
		}
		return (int) \ceil($firstDelay * 1000);
	}

	/**
	 * Will sleep for the defined amount of time
	 *
	 * @param string $ip
	 */
	public function sleepDelay($ip) {
		usleep($this->getDelay($ip) * 1000);
	}
}
