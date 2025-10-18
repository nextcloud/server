<?php

declare(strict_types=1);

/**
 * This file is based on the package: godruoyi/php-snowflake.
 * SPDX-FileCopyrightText: 2024 Godruoyi <g@godruoyi.com>
 * SPDX-License-Identifier: MIT
 */

namespace OC\DB\Snowflake;

class SnowflakeGenerator {
	public const MAX_TIMESTAMP_LENGTH = 42;
	public const MAX_DATACENTER_LENGTH = 5;
	public const MAX_WORKID_LENGTH = 5;
	public const MAX_CLI_LENGTH = 1;
	public const MAX_SEQUENCE_LENGTH = 10;
	public const MAX_SEQUENCE_SIZE = (-1 ^ (-1 << self::MAX_SEQUENCE_LENGTH));

	/**
	 * The data center id.
	 */
	protected int $datacenter;

	/**
	 * The worker id.
	 */
	protected int $workerId;

	/**
	 * The start timestamp.
	 */
	protected ?string $startTime = null;

	/**
	 * The last timestamp for the random generator.
	 */
	protected string $lastTimeStamp = '-1';

	/**
	 * The sequence number for the random generator.
	 */
	protected int $sequence = 0;

	/**
	 * Build Snowflake Instance.
	 */
	public function __construct(
		int $datacenter,
		int $workerId,
		private readonly NextcloudSequenceResolver $sequenceResolver,
		private readonly bool $isCLI,
	) {
		$maxDataCenter = -1 ^ (-1 << self::MAX_DATACENTER_LENGTH);
		$maxWorkId = -1 ^ (-1 << self::MAX_WORKID_LENGTH);

		// If not set datacenterID or workID, we will set a default value to use.
		$this->datacenter = $datacenter < 0 || $datacenter > $maxDataCenter ? random_int(0, 31) : $datacenter;
		$this->workerId = $workerId < 0 || $workerId > $maxWorkId ? random_int(0, 31) : $workerId;
	}

	/**
	 * @internal For unit tests only.
	 */
	public function is32BitsSystem(): bool {
		return PHP_INT_SIZE === 4;
	}

	/**
	 * Get snowflake id.
	 */
	public function nextId(): string {

		$currentTime = $this->getCurrentMillisecond();
		while (($sequence = $this->callResolver($currentTime)) > self::MAX_SEQUENCE_SIZE) {
			usleep(1);
			$currentTime = $this->getCurrentMillisecond();
		}

		$timestamp = $this->getStartTimeStamp();

		$isCLILeftMoveLength = self::MAX_SEQUENCE_LENGTH;
		$workerLeftMoveLength = self::MAX_CLI_LENGTH + $isCLILeftMoveLength;
		$datacenterLeftMoveLength = self::MAX_WORKID_LENGTH + $workerLeftMoveLength;
		$timestampLeftMoveLength = self::MAX_DATACENTER_LENGTH + $datacenterLeftMoveLength;

		if ($this->is32BitsSystem()) {
			// Version using gmp for 32-bits machine
			if (!function_exists('gmp_init')
				|| !function_exists('gmp_sub')
				|| !function_exists('gmp_add')
				|| !function_exists('gmp_strval')
				|| !function_exists('gmp_mul')
				|| !function_exists('gmp_pow')
			) {
				throw new \RuntimeException('gmp is a required extension on 32bits system.');
			}

			$currentTimeGmp = gmp_init($currentTime);
			$timestampGmp = gmp_init($timestamp);

			$gmpShiftLeft = static function (\GMP $num, int $bits): \GMP {
				return gmp_mul($num, gmp_pow(2, $bits));
			};

			$tsPart = $gmpShiftLeft(gmp_sub($currentTimeGmp, $timestampGmp), $timestampLeftMoveLength);
			$dcPart = $gmpShiftLeft(gmp_init($this->datacenter), $datacenterLeftMoveLength);
			$wkPart = $gmpShiftLeft(gmp_init($this->workerId), $workerLeftMoveLength);
			$cliPart = $gmpShiftLeft(gmp_init((int)$this->isCLI), $isCLILeftMoveLength);
			$seqPart = gmp_init($sequence);

			$id = gmp_add(gmp_add(gmp_add(gmp_add($tsPart, $dcPart), $wkPart), $cliPart), $seqPart);

			return gmp_strval($id);
		} else {
			// Dependency less version for 64-bits machine using bits-shifts
			return (string)((((int)$currentTime - (int)$timestamp) << $timestampLeftMoveLength)
				| ($this->datacenter << $datacenterLeftMoveLength)
				| ($this->workerId << $workerLeftMoveLength)
				| ((int)$this->isCLI << $isCLILeftMoveLength)
				| ($sequence));
		}
	}

	/**
	 * @internal Only for unit tests.
	 * @return array{
	 *      timestamp: string,
	 *      sequence: int,
	 *      workerid: int,
	 *      iscli: bool,
	 *      datacenter: int,
	 *  }
	 */
	public function parseId(string $id): array {
		if (!function_exists('gmp_init')
			|| !function_exists('gmp_and')
			|| !function_exists('gmp_div_q')
			|| !function_exists('gmp_pow')
			|| !function_exists('gmp_intval')
			|| !function_exists('gmp_strval')
		) {
			throw new \RuntimeException('gmp is a required extension to run parseId.');
		}
		$gmpShiftRight = function (\GMP $num, int $bits): \GMP {
			return gmp_div_q($num, gmp_pow(2, $bits));
		};

		$idGmp = gmp_init($id);

		$sequenceMask = gmp_init(self::MAX_SEQUENCE_SIZE);
		$sequence = gmp_and($idGmp, $sequenceMask);

		$isCli = gmp_and($gmpShiftRight($idGmp, self::MAX_SEQUENCE_LENGTH), gmp_init((1 << self::MAX_CLI_LENGTH) - 1));
		$worker = gmp_and($gmpShiftRight($idGmp, self::MAX_SEQUENCE_LENGTH + self::MAX_CLI_LENGTH), gmp_init((1 << self::MAX_WORKID_LENGTH) - 1));
		$datacenter = gmp_and($gmpShiftRight($idGmp, self::MAX_SEQUENCE_LENGTH + self::MAX_CLI_LENGTH + self::MAX_WORKID_LENGTH), gmp_init((1 << self::MAX_DATACENTER_LENGTH) - 1));

		$timestampDiff = $gmpShiftRight($idGmp, self::MAX_SEQUENCE_LENGTH + self::MAX_CLI_LENGTH + self::MAX_WORKID_LENGTH + self::MAX_DATACENTER_LENGTH);

		return[
			'timestamp' => gmp_strval($timestampDiff),
			'datacenter' => gmp_intval($datacenter),
			'workerid' => gmp_intval($worker),
			'iscli' => gmp_intval($isCli) === 1 ? true : false,
			'sequence' => gmp_intval($sequence),
		];
	}

	/**
	 * Get current millisecond time as a string.
	 * @internal For unit tests only.
	 */
	public function getCurrentMillisecond(): string {
		if ($this->is32BitsSystem()) {
			$time = microtime();
			$time = explode(' ', $time);
			return $time[1] . str_pad((string)floor((float)$time[0] * 1000), 3, '0', STR_PAD_LEFT);
		} else {
			return (string)(floor(microtime(true) * 1000));
		}
	}

	/**
	 * Set start time (second).
	 * @throw \InvalidArgumentException
	 */
	public function setStartTimeStamp(int $second): self {
		if ($this->is32BitsSystem()) {
			if (!function_exists('gmp_init')) {
				throw new \RuntimeException('gmp is a required extension to run on 32 bit system.');
			}

			$missTime = gmp_sub($this->getCurrentMillisecond(), gmp_mul($second, 1000));

			if (gmp_cmp($missTime, 0) < 0) {
				throw new \InvalidArgumentException('The start time cannot be greater than the current time');
			}
			$mask = gmp_init(-1);
			$shifted = gmp_mul(gmp_init(-1), gmp_pow(2, self::MAX_TIMESTAMP_LENGTH)); // -1 << n
			$maxTimeDiff = gmp_xor($mask, $shifted);

			if (gmp_cmp($missTime, $maxTimeDiff) > 0) {
				throw new \InvalidArgumentException(
					sprintf('The current microtime - starttime is not allowed to exceed -1 ^ (-1 << %d), You can reset the start time to fix this', self::MAX_TIMESTAMP_LENGTH)
				);
			}
			$this->startTime = gmp_strval(gmp_mul($second, 1000));
		} else {
			$missTime = (int)$this->getCurrentMillisecond() - $second * 1000;

			if ($missTime < 0) {
				throw new \InvalidArgumentException('The start time cannot be greater than the current time. starttime=' . $second * 1000 . ' current=' . $this->getCurrentMillisecond());
			}

			$maxTimeDiff = -1 ^ (-1 << self::MAX_TIMESTAMP_LENGTH);

			if ($missTime > $maxTimeDiff) {
				throw new \InvalidArgumentException(
					sprintf('The current microtime - starttime is not allowed to exceed -1 ^ (-1 << %d), You can reset the start time to fix this', self::MAX_TIMESTAMP_LENGTH)
				);
			}
			$this->startTime = (string)($second * 1000);
		}

		return $this;
	}

	/**
	 * Get start timestamp (millisecond), If not set default to 2019-08-08 08:08:08.
	 */
	public function getStartTimeStamp(): string {
		if (! is_null($this->startTime)) {
			return $this->startTime;
		}

		// We set a default start time if you not set.
		if ($this->is32BitsSystem()) {
			return gmp_strval(gmp_mul(strtotime('2025-01-01'), 1000));
		} else {
			return (string)(strtotime('2025-01-01') * 1000);
		}
	}

	/**
	 * Call resolver.
	 */
	protected function callResolver(string $currentTime): int {
		// Memcache based resolver
		if ($this->sequenceResolver->isAvailable()) {
			return $this->sequenceResolver->sequence($currentTime);
		}

		// random fallback
		if ($this->lastTimeStamp === $currentTime) {
			$this->sequence++;
			$this->lastTimeStamp = $currentTime;

			return $this->sequence;
		}

		$this->sequence = crc32(uniqid((string)random_int(0, PHP_INT_MAX), true)) % self::MAX_SEQUENCE_SIZE;
		$this->lastTimeStamp = $currentTime;

		return $this->sequence;
	}
}
