<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Riedel <joeried@users.noreply.github.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\Security\Bruteforce;

use OC\Security\Bruteforce\Backend\IBackend;
use OC\Security\Normalizer\IpAddress;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\Bruteforce\MaxDelayReached;
use Psr\Log\LoggerInterface;

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
class Throttler implements IThrottler {
	/** @var bool[] */
	private array $hasAttemptsDeleted = [];
	/** @var bool[] */
	private array $ipIsWhitelisted = [];

	public function __construct(
		private ITimeFactory $timeFactory,
		private LoggerInterface $logger,
		private IConfig $config,
		private IBackend $backend,
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(string $action,
		string $ip,
		array $metadata = []): void {
		// No need to log if the bruteforce protection is disabled
		if (!$this->config->getSystemValueBool('auth.bruteforce.protection.enabled', true)) {
			return;
		}

		$ipAddress = new IpAddress($ip);
		if ($this->isBypassListed((string)$ipAddress)) {
			return;
		}

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

		$this->backend->registerAttempt(
			(string)$ipAddress,
			$ipAddress->getSubnet(),
			$this->timeFactory->getTime(),
			$action,
			$metadata
		);
	}

	/**
	 * Check if the IP is whitelisted
	 */
	public function isBypassListed(string $ip): bool {
		if (isset($this->ipIsWhitelisted[$ip])) {
			return $this->ipIsWhitelisted[$ip];
		}

		if (!$this->config->getSystemValueBool('auth.bruteforce.protection.enabled', true)) {
			$this->ipIsWhitelisted[$ip] = true;
			return true;
		}

		$keys = $this->config->getAppKeys('bruteForce');
		$keys = array_filter($keys, function ($key) {
			return str_starts_with($key, 'whitelist_');
		});

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$type = 4;
		} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$type = 6;
		} else {
			$this->ipIsWhitelisted[$ip] = false;
			return false;
		}

		$ip = inet_pton($ip);

		foreach ($keys as $key) {
			$cidr = $this->config->getAppValue('bruteForce', $key, null);

			$cx = explode('/', $cidr);
			$addr = $cx[0];
			$mask = (int)$cx[1];

			// Do not compare ipv4 to ipv6
			if (($type === 4 && !filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ||
				($type === 6 && !filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
				continue;
			}

			$addr = inet_pton($addr);

			$valid = true;
			for ($i = 0; $i < $mask; $i++) {
				$part = ord($addr[(int)($i / 8)]);
				$orig = ord($ip[(int)($i / 8)]);

				$bitmask = 1 << (7 - ($i % 8));

				$part = $part & $bitmask;
				$orig = $orig & $bitmask;

				if ($part !== $orig) {
					$valid = false;
					break;
				}
			}

			if ($valid === true) {
				$this->ipIsWhitelisted[$ip] = true;
				return true;
			}
		}

		$this->ipIsWhitelisted[$ip] = false;
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function showBruteforceWarning(string $ip, string $action = ''): bool {
		$attempts = $this->getAttempts($ip, $action);
		// 4 failed attempts is the last delay below 5 seconds
		return $attempts >= 4;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttempts(string $ip, string $action = '', float $maxAgeHours = 12): int {
		if ($maxAgeHours > 48) {
			$this->logger->error('Bruteforce has to use less than 48 hours');
			$maxAgeHours = 48;
		}

		if ($ip === '' || isset($this->hasAttemptsDeleted[$action])) {
			return 0;
		}

		$ipAddress = new IpAddress($ip);
		if ($this->isBypassListed((string)$ipAddress)) {
			return 0;
		}

		$maxAgeTimestamp = (int) ($this->timeFactory->getTime() - 3600 * $maxAgeHours);

		return $this->backend->getAttempts(
			$ipAddress->getSubnet(),
			$maxAgeTimestamp,
			$action !== '' ? $action : null,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDelay(string $ip, string $action = ''): int {
		$attempts = $this->getAttempts($ip, $action);
		if ($attempts === 0) {
			return 0;
		}

		$firstDelay = 0.1;
		if ($attempts > self::MAX_ATTEMPTS) {
			// Don't ever overflow. Just assume the maxDelay time:s
			return self::MAX_DELAY_MS;
		}

		$delay = $firstDelay * 2 ** $attempts;
		if ($delay > self::MAX_DELAY) {
			return self::MAX_DELAY_MS;
		}
		return (int) \ceil($delay * 1000);
	}

	/**
	 * {@inheritDoc}
	 */
	public function resetDelay(string $ip, string $action, array $metadata): void {
		// No need to log if the bruteforce protection is disabled
		if (!$this->config->getSystemValueBool('auth.bruteforce.protection.enabled', true)) {
			return;
		}

		$ipAddress = new IpAddress($ip);
		if ($this->isBypassListed((string)$ipAddress)) {
			return;
		}

		$this->backend->resetAttempts(
			$ipAddress->getSubnet(),
			$action,
			$metadata,
		);

		$this->hasAttemptsDeleted[$action] = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function resetDelayForIP(string $ip): void {
		// No need to log if the bruteforce protection is disabled
		if (!$this->config->getSystemValueBool('auth.bruteforce.protection.enabled', true)) {
			return;
		}

		$ipAddress = new IpAddress($ip);
		if ($this->isBypassListed((string)$ipAddress)) {
			return;
		}

		$this->backend->resetAttempts($ipAddress->getSubnet());
	}

	/**
	 * {@inheritDoc}
	 */
	public function sleepDelay(string $ip, string $action = ''): int {
		$delay = $this->getDelay($ip, $action);
		if (!$this->config->getSystemValueBool('auth.bruteforce.protection.testing')) {
			usleep($delay * 1000);
		}
		return $delay;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sleepDelayOrThrowOnMax(string $ip, string $action = ''): int {
		$delay = $this->getDelay($ip, $action);
		if (($delay === self::MAX_DELAY_MS) && $this->getAttempts($ip, $action, 0.5) > self::MAX_ATTEMPTS) {
			$this->logger->info('IP address blocked because it reached the maximum failed attempts in the last 30 minutes [action: {action}, ip: {ip}]', [
				'action' => $action,
				'ip' => $ip,
			]);
			// If the ip made too many attempts within the last 30 mins we don't execute anymore
			throw new MaxDelayReached('Reached maximum delay');
		}
		if ($delay > 100) {
			$this->logger->info('IP address throttled because it reached the attempts limit in the last 30 minutes [action: {action}, delay: {delay}, ip: {ip}]', [
				'action' => $action,
				'ip' => $ip,
				'delay' => $delay,
			]);
		}
		if (!$this->config->getSystemValueBool('auth.bruteforce.protection.testing')) {
			usleep($delay * 1000);
		}
		return $delay;
	}
}
