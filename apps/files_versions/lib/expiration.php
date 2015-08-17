<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Versions;

use \OCP\IConfig;
use \OCP\AppFramework\Utility\ITimeFactory;

class Expiration {

	// how long do we keep files a version if no other value is defined in the config file (unit: days)
	const DEFAULT_RETENTION_OBLIGATION = 30;
	const NO_OBLIGATION = -1;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var string */
	private $retentionObligation;

	/** @var int */
	private $minAge;

	/** @var int */
	private $maxAge;

	/** @var bool */
	private $canPurgeToSaveSpace;

	public function __construct(IConfig $config,ITimeFactory $timeFactory){
		$this->timeFactory = $timeFactory;
		$this->retentionObligation = $config->getSystemValue('versions_retention_obligation', 'auto');

		if ($this->retentionObligation !== 'disabled') {
			$this->parseRetentionObligation();
		}
	}

	/**
	 * Is versions expiration enabled
	 * @return bool
	 */
	public function isEnabled(){
		return $this->retentionObligation !== 'disabled';
	}

	/**
	 * Is default expiration active
	 */
	public function shouldAutoExpire(){
		return $this->minAge === self::NO_OBLIGATION
				|| $this->maxAge === self::NO_OBLIGATION;
	}

	/**
	 * Check if given timestamp in expiration range
	 * @param int $timestamp
	 * @param bool $quotaExceeded
	 * @return bool
	 */
	public function isExpired($timestamp, $quotaExceeded = false){
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
		if ($time<$timestamp) {
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

	private function parseRetentionObligation(){
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
			$this->minAge = intval($minValue);
			$this->maxAge = self::NO_OBLIGATION;
			$this->canPurgeToSaveSpace = true;
		} elseif ($minValue === 'auto' && $maxValue !== 'auto') {
			// Delete anytime if space needed, Delete all older than max automatically
			$this->minAge = self::NO_OBLIGATION;
			$this->maxAge = intval($maxValue);
			$this->canPurgeToSaveSpace = true;
		} elseif ($minValue !== 'auto' && $maxValue !== 'auto') {
			// Delete all older than max OR older than min if space needed

			// Max < Min as per https://github.com/owncloud/core/issues/16301
			if ($maxValue < $minValue) {
				$maxValue = $minValue;
			}

			$this->minAge = intval($minValue);
			$this->maxAge = intval($maxValue);
			$this->canPurgeToSaveSpace = false;
		}
	}
}
