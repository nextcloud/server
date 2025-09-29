<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Log;

use Psr\Log\LoggerInterface;

/**
 * Interface ILogFactory
 *
 * @since 14.0.0
 */
interface ILogFactory {
	/**
	 * @param string $type - one of: file, errorlog, syslog, systemd
	 * @return IWriter
	 * @since 14.0.0
	 */
	public function get(string $type): IWriter;

	/**
	 * @param string $path
	 * @param string $type
	 * @param string $tag
	 * @return LoggerInterface
	 * @since 22.0.0 - Parameters $type and $tag were added in 24.0.0
	 */
	public function getCustomPsrLogger(string $path, string $type = 'file', string $tag = 'Nextcloud'): LoggerInterface;
}
