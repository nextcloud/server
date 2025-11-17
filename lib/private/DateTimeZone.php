<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\ISession;
use OCP\Server;
use Psr\Log\LoggerInterface;

class DateTimeZone implements IDateTimeZone {
	/**
	 * Constructor
	 *
	 * @param IConfig $config
	 * @param ISession $session
	 */
	public function __construct(
		protected IConfig $config,
		protected ISession $session,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getTimeZone($timestamp = false, ?string $userId = null): \DateTimeZone {
		$uid = $userId ?? $this->session->get('user_id');
		$timezoneName = $this->config->getUserValue($uid, 'core', 'timezone', '');
		if ($timezoneName === '') {
			if ($uid === $userId && $this->session->exists('timezone')) {
				return $this->guessTimeZoneFromOffset($this->session->get('timezone'), $timestamp);
			}
			return $this->getDefaultTimeZone();
		}

		try {
			return new \DateTimeZone($timezoneName);
		} catch (\Exception $e) {
			Server::get(LoggerInterface::class)->debug('Failed to created DateTimeZone "' . $timezoneName . '"', ['app' => 'datetimezone']);
			return $this->getDefaultTimeZone();
		}
	}

	public function getDefaultTimeZone(): \DateTimeZone {
		/** @var non-empty-string */
		$timezone = $this->config->getSystemValueString('default_timezone', 'UTC');
		try {
			return new \DateTimeZone($timezone);
		} catch (\Exception) {
			// its always UTC see lib/base.php
			return new \DateTimeZone('UTC');
		}
	}

	/**
	 * Guess the DateTimeZone for a given offset
	 *
	 * We first try to find a Etc/GMT* timezone, if that does not exist,
	 * we try to find it manually, before falling back to UTC.
	 *
	 * @param mixed $offset
	 * @param int|false $timestamp
	 * @return \DateTimeZone
	 */
	protected function guessTimeZoneFromOffset($offset, $timestamp) {
		try {
			// Note: the timeZone name is the inverse to the offset,
			// so a positive offset means negative timeZone
			// and the other way around.
			if ($offset > 0) {
				$timeZone = 'Etc/GMT-' . $offset;
			} else {
				$timeZone = 'Etc/GMT+' . abs($offset);
			}

			return new \DateTimeZone($timeZone);
		} catch (\Exception $e) {
			// If the offset has no Etc/GMT* timezone,
			// we try to guess one timezone that has the same offset
			foreach (\DateTimeZone::listIdentifiers() as $timeZone) {
				$dtz = new \DateTimeZone($timeZone);
				$dateTime = new \DateTime();

				if ($timestamp !== false) {
					$dateTime->setTimestamp($timestamp);
				}

				$dtOffset = $dtz->getOffset($dateTime);
				if ($dtOffset == 3600 * $offset) {
					return $dtz;
				}
			}

			// No timezone found, fallback to UTC
			Server::get(LoggerInterface::class)->debug('Failed to find DateTimeZone for offset "' . $offset . '"', ['app' => 'datetimezone']);
			return $this->getDefaultTimeZone();
		}
	}
}
