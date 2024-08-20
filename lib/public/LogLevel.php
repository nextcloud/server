<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel as PsrLogLevel;

/**
 * Logging levels according to RFC 5424
 * @since 31.0.0
 */
enum LogLevel: int {
	/** Detailed debug information */
	case DEBUG = 0;
	/** Interesting events */
	case INFO = 1;
	/** Normal but significant events */
	case NOTICE = 2;
	/** Exceptional occurrences that are not errors */
	case WARNING = 3;
	/** Runtime errors that do not require immediate action but should typically be logged and monitored */
	case ERROR = 4;
	/** Critical conditions */
	case CRITICAL = 5;
	/** Action must be taken immediately */
	case ALERT = 6;
	/** System is unusable */
	case EMERGENCY = 7;

	/**
	 * Create new `\OCP\LogLevel` from a given PSR-3 LogLevel
	 * @param string $level the PSR-3 log level to convert
	 * @throws InvalidArgumentException If the `$level` is not a PSR log level
	 * @since 31.0.0
	 */
	public static function fromPsrLogLevel(string $level): self {
		return match($level) {
			PsrLogLevel::DEBUG => self::DEBUG,
			PsrLogLevel::INFO => self::INFO,
			PsrLogLevel::NOTICE => self::NOTICE,
			PsrLogLevel::WARNING => self::WARNING,
			PsrLogLevel::ERROR => self::ERROR,
			PsrLogLevel::CRITICAL => self::CRITICAL,
			PsrLogLevel::ALERT => self::ALERT,
			PsrLogLevel::EMERGENCY => self::EMERGENCY,
			default => throw new InvalidArgumentException($level . ' is not a valid PSR log level'),
		};
	}

	/**
	 * Convert this log level to a PSR-3 `\Psr\Log\LogLevel`
	 * @since 31.0.0
	 */
	public function toPsrLogLevel(): string {
		return match($this) {
			self::DEBUG => PsrLogLevel::DEBUG,
			self::INFO => PsrLogLevel::INFO,
			self::NOTICE => PsrLogLevel::NOTICE,
			self::WARNING => PsrLogLevel::WARNING,
			self::ERROR => PsrLogLevel::ERROR,
			self::CRITICAL => PsrLogLevel::CRITICAL,
			self::ALERT => PsrLogLevel::ALERT,
			self::EMERGENCY => PsrLogLevel::EMERGENCY,
		};
	}
}
