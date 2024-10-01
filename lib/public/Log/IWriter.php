<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Log;

/**
 * Interface IWriter
 *
 * @since 14.0.0
 */
interface IWriter {
	/**
	 * @since 14.0.0
	 *
	 * @param string $app
	 * @param string|array $message
	 * @param int $level
	 */
	public function write(string $app, $message, int $level);
}
