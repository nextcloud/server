<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\EntityContext;

/**
 * Interface IContextPortation
 *
 * Occasionally an IEntity needs to be reused not in the same, but a new
 * request. As IEntities receive custom context information during a flow
 * cycle, sometimes it might be necessary to export context identifiers to
 * be able to recreate the state at a later point. For example: handling
 * translations in a notification INotifier.
 *
 *
 * @since 20.0.0
 */
interface IContextPortation {
	/**
	 * All relevant context identifiers that are needed to restore the state
	 * of an entity shall be returned with this method. The resulting array
	 * must be JSON-serializable.
	 *
	 * @since 20.0.0
	 */
	public function exportContextIDs(): array;

	/**
	 * This method receives the array as returned by `exportContextIDs()` in
	 * order to restore the state of the IEntity if necessary.
	 *
	 * @since 20.0.0
	 */
	public function importContextIDs(array $contextIDs): void;
}
