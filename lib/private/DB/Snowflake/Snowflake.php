<?php

declare(strict_types=1);

/**
 * This file is based on tourze/symfony-snowflake-bundle
 * SPDX-FileCopyrightText: tourze <https://github.com/tourze>
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: MIT
 */

namespace OC\DB\Snowflake;

use OCP\DB\ISnowflake;

class Snowflake implements ISnowflake {

	/**
	 * @var SnowflakeGenerator[]
	 */
	private static array $generators = [];

	public static function getGenerator(int $datacenter, int $workerId, bool $isCLI, NextcloudSequenceResolver $resolver): SnowflakeGenerator {
		$key = "{$datacenter}-{$workerId}";
		if (!isset(self::$generators[$key])) {
			$generator = new SnowflakeGenerator(
				$datacenter,
				$workerId,
				$resolver,
				$isCLI,
			);
			$generator->setStartTimeStamp(strtotime('2025-01-01'));
			self::$generators[$key] = $generator;
		}
		return self::$generators[$key];
	}

	public static function generateWorkerId(string $hostname, int $maxWorkerId = 31): int {
		$hash = crc32($hostname);
		return $hash % ($maxWorkerId + 1);
	}

	private SnowflakeGenerator $generator;

	public function __construct(
		NextcloudSequenceResolver $nextcloudSequenceResolver,
		bool $isCLI,
	) {
		$this->generator = static::getGenerator(
			-1, // ATM set randomly
			self::generateWorkerId(gethostname()),
			$isCLI,
			$nextcloudSequenceResolver
		);
	}

	public function nextId(): string {
		return $this->generator->nextId();
	}
}
