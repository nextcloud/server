<?php

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
	 * @param array $parts
	 *
	 * @return ICompositeExpression
	 * @since 8.2.0
	 */
	public function addMultiple(array $parts = []): ICompositeExpression;

	/**
	 * Adds an expression to composite expression.
	 *
	 * @param mixed $part
	 *
	 * @return ICompositeExpression
	 * @since 8.2.0
	 */
	public function add($part): ICompositeExpression;

	/**
	 * Retrieves the amount of expressions on composite expression.
	 *
	 * @return integer
	 * @since 8.2.0
	 */
	public function count(): int;

	/**
	 * Returns the type of this composite expression (AND/OR).
	 *
	 * @return string
	 * @since 8.2.0
	 */
	public function getType(): string;
}
