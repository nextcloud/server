<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Log;

/**
 * Interface IDataLogger
 *
 * @since 18.0.1
 */
interface IDataLogger {
	/**
	 * allows to log custom data, similar to how logException works
	 *
	 * @since 18.0.1
	 */
	public function logData(string $message, array $data, array $context = []): void;
}
