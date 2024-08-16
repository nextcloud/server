<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Interface IEntityEvent
 *
 * represents an entity event that is dispatched via EventDispatcher
 *
 *
 * @since 18.0.0
 */
interface IEntityEvent {
	/**
	 * returns a translated name to be presented in the web interface.
	 *
	 * Example: "created" (en), "kreita" (eo)
	 *
	 * @since 18.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * returns the event name that is emitted by the EventDispatcher, e.g.:
	 *
	 * Example: "OCA\MyApp\Factory\Cats::postCreated"
	 *
	 * @since 18.0.0
	 */
	public function getEventName(): string;
}
