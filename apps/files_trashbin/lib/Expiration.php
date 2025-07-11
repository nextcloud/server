<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;

class Expiration {

	// how long do we keep files in the trash bin if no other value is defined in the config file (unit: days)
	public const DEFAULT_RETENTION_OBLIGATION = 30;
	public const NO_OBLIGATION = -1;

	/** @var string */
	private $retentionObligation;

	/** @var int */
	private $minAge;

	/** @var int */
	private $maxAge;

	/** @var bool */
	private $canPurgeToSaveSpace;

	public function __construct(
		IConfig $config,
		private ITimeFactory $timeFactory,
	) {
		$this->setRetentionObligation($config->getSystemValue('trashbin_retention_obligation', 'auto'));
	}

	public function setRetentionObligation(string $obligation) {
		$this->retentionObligation = $obligation;

		if ($this->retentionObligation !== 'disabled') {
			$this->parseRetentionObligation();
		}
	}

	/**
	 * Is trashbin expiration enabled
	 * @return bool
	 */
	public function isEnabled() {
		return $this->retentionObligation !== 'disabled';
	}

	/**
	 * Check if given timestamp in expiration range
	 * @param int $timestamp
	 * @param bool $quotaExceeded
	 * @return bool
	 */
	public function isExpired($timestamp, $quotaExceeded = false) {
		// No expiration if disabled
		if (!$this->isEnabled()) {
			return false;
		}

		// Purge to save space (if allowed)
		if ($quotaExceeded && $this->canPurgeToSaveSpace) {
			return true;
		}

		$time = $this->timeFactory->getTime();
		// Never expire dates in future e.g. misconfiguration or negative time
		// adjustment
		if ($time < $timestamp) {
			return false;
		}

		// Purge as too old
		if ($this->maxAge !== self::NO_OBLIGATION) {
			$maxTimestamp = $time - ($this->maxAge * 86400);
			$isOlderThanMax = $timestamp < $maxTimestamp;
		} else {
			$isOlderThanMax = false;
		}

		if ($this->minAge !== self::NO_OBLIGATION) {
			// older than Min obligation and we are running out of quota?
			$minTimestamp = $time - ($this->minAge * 86400);
			$isMinReached = ($timestamp < $minTimestamp) && $quotaExceeded;
		} else {
			$isMinReached = false;
		}

		return $isOlderThanMax || $isMinReached;
	}

	/**
	 * Get minimal retention obligation as a timestamp
	 *
	 * @return int|false
	 */
	public function getMinAgeAsTimestamp() {
		$minAge = false;
		if ($this->isEnabled() && $this->minAge !== self::NO_OBLIGATION) {
			$time = $this->timeFactory->getTime();
			$minAge = $time - ($this->minAge * 86400);
		}
		return $minAge;
	}

	/**
	 * @return bool|int
	 */
	public function getMaxAgeAsTimestamp() {
		$maxAge = false;
		if ($this->isEnabled() && $this->maxAge !== self::NO_OBLIGATION) {
			$time = $this->timeFactory->getTime();
			$maxAge = $time - ($this->maxAge * 86400);
		}
		return $maxAge;
	}

	private function parseRetentionObligation() {
		$splitValues = explode(',', $this->retentionObligation);
		if (!isset($splitValues[0])) {
			$minValue = self::DEFAULT_RETENTION_OBLIGATION;
		} else {
			$minValue = trim($splitValues[0]);
		}

		if (!isset($splitValues[1]) && $minValue === 'auto') {
			$maxValue = 'auto';
		} elseif (!isset($splitValues[1])) {
			$maxValue = self::DEFAULT_RETENTION_OBLIGATION;
		} else {
			$maxValue = trim($splitValues[1]);
		}

		if ($minValue === 'auto' && $maxValue === 'auto') {
			// Default: Keep for 30 days but delete anytime if space needed
			$this->minAge = self::DEFAULT_RETENTION_OBLIGATION;
			$this->maxAge = self::NO_OBLIGATION;
			$this->canPurgeToSaveSpace = true;
		} elseif ($minValue !== 'auto' && $maxValue === 'auto') {
			// Keep for X days but delete anytime if space needed
			$this->minAge = (int)$minValue;
			$this->maxAge = self::NO_OBLIGATION;
			$this->canPurgeToSaveSpace = true;
		} elseif ($minValue === 'auto' && $maxValue !== 'auto') {
			// Delete anytime if space needed, Delete all older than max automatically
			$this->minAge = self::NO_OBLIGATION;
			$this->maxAge = (int)$maxValue;
			$this->canPurgeToSaveSpace = true;
		} elseif ($minValue !== 'auto' && $maxValue !== 'auto') {
			// Delete all older than max OR older than min if space needed

			// Max < Min as per https://github.com/owncloud/core/issues/16300
			if ($maxValue < $minValue) {
				$maxValue = $minValue;
			}

			$this->minAge = (int)$minValue;
			$this->maxAge = (int)$maxValue;
			$this->canPurgeToSaveSpace = false;
		}
	}
}
