<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\EventDispatcher;

/**
 * @since 17.0.0
 *
 * @template T of Event
 */
interface IEventListener {
	/**
	 * @param Event $event
	 * @psalm-param T $event
	 *
	 * @since 17.0.0
	 */
	public function handle(Event $event): void;
}
