<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Interface ICheck
 *
 * @since 9.1
 */
interface ICheck {
	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 * @since 9.1
	 */
	public function executeCheck($operator, $value);

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 * @since 9.1
	 */
	public function validateCheck($operator, $value);

	/**
	 * returns a list of Entities the checker supports. The values must match
	 * the class name of the entity.
	 *
	 * An empty result means the check is universally available.
	 *
	 * @since 18.0.0
	 */
	public function supportedEntities(): array;

	/**
	 * returns whether the operation can be used in the requested scope.
	 *
	 * Scope IDs are defined as constants in OCP\WorkflowEngine\IManager. At
	 * time of writing these are SCOPE_ADMIN and SCOPE_USER.
	 *
	 * For possibly unknown future scopes the recommended behaviour is: if
	 * user scope is permitted, the default behaviour should return `true`,
	 * otherwise `false`.
	 *
	 * @since 18.0.0
	 */
	public function isAvailableForScope(int $scope): bool;
}
