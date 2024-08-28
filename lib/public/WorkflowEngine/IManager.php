<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Interface IManager
 *
 * @since 9.1
 */
interface IManager {
	/**
	 * @since 18.0.0
	 */
	public const SCOPE_ADMIN = 0;

	/**
	 * @since 18.0.0
	 */
	public const SCOPE_USER = 1;

	/**
	 * @since 21.0.0
	 */
	public const MAX_CHECK_VALUE_BYTES = 2048;

	/**
	 * @since 21.0.0
	 */
	public const MAX_OPERATION_VALUE_BYTES = 4096;

	/**
	 * Listen to `OCP\WorkflowEngine\Events\RegisterEntitiesEvent` at the
	 * IEventDispatcher for registering your entities.
	 *
	 * @since 18.0.0
	 */
	public function registerEntity(IEntity $entity): void;

	/**
	 * Listen to `OCP\WorkflowEngine\Events\RegisterOperationsEvent` at the
	 * IEventDispatcher for registering your operators.
	 *
	 * @since 18.0.0
	 */
	public function registerOperation(IOperation $operator): void;

	/**
	 * Listen to `OCP\WorkflowEngine\Events\RegisterChecksEvent` at the
	 * IEventDispatcher for registering your operators.
	 *
	 * @since 18.0.0
	 */
	public function registerCheck(ICheck $check): void;

	/**
	 * @since 18.0.0
	 */
	public function getRuleMatcher(): IRuleMatcher;
}
