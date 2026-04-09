<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Log;

/**
 * Interface IFileBased
 *
 *
 * @since 14.0.0
 */
interface IFileBased {
	/**
	 * @since 14.0.0
	 */
	public function getLogFilePath():string;

	/**
	 * @since 14.0.0
	 */
	public function getEntries(int $limit = 50, int $offset = 0): array;
}
