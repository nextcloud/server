<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Bruteforce;

use OC\Security\Bruteforce\Backend\IBackend;
use OC\Security\Ip\BruteforceAllowList;
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

	public function __construct(
		private ITimeFactory $timeFactory,
		private LoggerInterface $logger,
		private IConfig $config,
		private IBackend $backend,
		private BruteforceAllowList $allowList,
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
		return $this->allowList->isBypassListed($ip);
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

		$maxAgeTimestamp = (int)($this->timeFactory->getTime() - 3600 * $maxAgeHours);

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
		if ($attempts > $this->config->getSystemValueInt('auth.bruteforce.max-attempts', self::MAX_ATTEMPTS)) {
			// Don't ever overflow. Just assume the maxDelay time:s
			return self::MAX_DELAY_MS;
		}

		$delay = $firstDelay * 2 ** $attempts;
		if ($delay > self::MAX_DELAY) {
			return self::MAX_DELAY_MS;
		}
		return (int)\ceil($delay * 1000);
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
		if (($delay === self::MAX_DELAY_MS) && $this->getAttempts($ip, $action, 0.5) > $this->config->getSystemValueInt('auth.bruteforce.max-attempts', self::MAX_ATTEMPTS)) {
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
