<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Nextcloud logging levels.
 * For historical reasons the logging levels are provided as interface constants.
 *
 * @since 7.0.0
 * @since 20.0.0 deprecated logging methods in favor of \Psr\Log\LoggerInterface
 * @since 31.0.0 removed deprecated logging methods - the interface is kept for Nextcloud log levels
 */
interface ILogger {
	/**
	 * @since 14.0.0
	 */
	public const DEBUG = 0;
	/**
	 * @since 14.0.0
	 */
	public const INFO = 1;
	/**
	 * @since 14.0.0
	 */
	public const WARN = 2;
	/**
	 * @since 14.0.0
	 */
	public const ERROR = 3;
	/**
	 * @since 14.0.0
	 */
	public const FATAL = 4;
}
