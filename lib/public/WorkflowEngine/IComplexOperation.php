<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Interface IComplexOperation
 *
 * This interface represents an operator that is less generic and indicates
 * that some of the tasks it does itself instead of relying on the engine.
 * This includes:
 *
 * * registering listeners – the implementing app needs to ensure that the
 *   business logic registers listeners to the events it listens to. For example
 *   when direct storage access is required, adding a wrapper or listening to
 *   a specific one is required over usual file events.
 *
 *
 * @since 18.0.0
 */
interface IComplexOperation extends IOperation {
	/**
	 * As IComplexOperation chooses the triggering events itself, a hint has
	 * to be shown to the user so make clear when this operation is becoming
	 * active. This method returns such a translated string.
	 *
	 * Example: "When a file is accessed" (en)
	 *
	 * @since 18.0.0
	 */
	public function getTriggerHint(): string;
}
