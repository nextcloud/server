<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Interface ISpecificOperation
 *
 * This interface represents an operator that is designed to work with exactly
 * one entity type.
 *
 * In almost all of the cases it is not necessary to have this limitation,
 * because the action is not connected to the event. This mechanism suits
 * special cases.
 *
 * @since 18.0.0
 */
interface ISpecificOperation extends IOperation {
	/**
	 * returns the id of the entity the operator is designed for
	 *
	 * Example: 'WorkflowEngine_Entity_File'
	 *
	 * @since 18.0.0
	 */
	public function getEntityId():string;
}
