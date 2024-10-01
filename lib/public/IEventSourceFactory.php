<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

/**
 * @since 28.0.0
 */
interface IEventSourceFactory {
	/**
	 * Create a new event source
	 *
	 * @return IEventSource
	 * @since 28.0.0
	 */
	public function create(): IEventSource;
}
