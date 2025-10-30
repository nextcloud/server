<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TaskProcessing;

/**
 * This is the interface that is implemented by apps that
 * implement a task processing provider with support for being triggered
 * @since 33.0.0
 */
interface ITriggerableProvider extends IProvider {

	/**
	 * Called when new tasks for this provider are coming in and there are currently
	 * no tasks running for this provider's task type
	 *
	 * @since 33.0.0
	 */
	public function trigger(): void;
}
