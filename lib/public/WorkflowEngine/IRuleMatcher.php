<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

use RuntimeException;

/**
 * Class IRuleMatcher
 *
 *
 * @since 18.0.0
 */
interface IRuleMatcher extends IFileCheck {
	/**
	 * This method is left for backwards compatibility and easier porting of
	 * apps. Please use 'getFlows' instead (and setOperation if you implement
	 * an IComplexOperation).
	 *
	 * @since 18.0.0
	 * @deprecated 18.0.0
	 */
	public function getMatchingOperations(string $class, bool $returnFirstMatchingOperationOnly = true): array;

	/**
	 * @throws RuntimeException
	 * @since 18.0.0
	 */
	public function getFlows(bool $returnFirstMatchingOperationOnly = true): array;

	/**
	 * this method can only be called once and is typically called by the
	 * Flow engine, unless for IComplexOperations.
	 *
	 * @throws RuntimeException
	 * @since 18.0.0
	 */
	public function setOperation(IOperation $operation): void;

	/**
	 * this method can only be called once and is typically called by the
	 * Flow engine, unless for IComplexOperations.
	 *
	 * @throws RuntimeException
	 * @since 18.0.0
	 */
	public function setEntity(IEntity $entity): void;

	/**
	 * returns the entity which might provide more information, depending on
	 * the interfaces it implements
	 *
	 * @return IEntity
	 * @since 18.0.0
	 */
	public function getEntity(): IEntity;

	/**
	 * this method can be called once to set the event name that is currently
	 * being processed. The workflow engine takes care of this usually, only an
	 * IComplexOperation might want to make use of it.
	 *
	 * @throws RuntimeException
	 * @since 20.0.0
	 */
	public function setEventName(string $eventName): void;
}
