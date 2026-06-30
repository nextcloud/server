<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\DB\QueryBuilder;

/**
 * This class provides a wrapper around Doctrine's CompositeExpression
 * @since 8.2.0
 */
interface ICompositeExpression {
	/**
	 * Adds multiple parts to composite expression.
	 *
	 *
	 * @since 8.2.0
	 */
	public function addMultiple(array $parts = []): ICompositeExpression;

	/**
	 * Adds an expression to composite expression.
	 *
	 * @param mixed $part
	 *
	 * @since 8.2.0
	 */
	public function add($part): ICompositeExpression;

	/**
	 * Retrieves the amount of expressions on composite expression.
	 *
	 * @since 8.2.0
	 */
	public function count(): int;

	/**
	 * Returns the type of this composite expression (AND/OR).
	 *
	 * @since 8.2.0
	 */
	public function getType(): string;

	/**
	 * Case the composite expression to string.
	 * @since 34.0.0
	 */
	public function __toString(): string;
}
