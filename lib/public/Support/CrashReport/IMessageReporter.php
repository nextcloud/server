<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\CrashReport;

/**
 * @since 17.0.0
 */
interface IMessageReporter extends IReporter {
	/**
	 * Report a (error) message
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @since 17.0.0
	 */
	public function reportMessage(string $message, array $context = []): void;
}
