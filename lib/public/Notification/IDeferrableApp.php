<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Notification;

/**
 * Interface IDeferrableApp
 *
 * @since 20.0.0
 */
interface IDeferrableApp extends IApp {
	/**
	 * Start deferring notifications until `flush()` is called
	 *
	 * @since 20.0.0
	 */
	public function defer(): void;

	/**
	 * Send all deferred notifications that have been stored since `defer()` was called
	 *
	 * @since 20.0.0
	 */
	public function flush(): void;
}
