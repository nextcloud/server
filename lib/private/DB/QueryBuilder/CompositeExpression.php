<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder;

use OCP\DB\QueryBuilder\ICompositeExpression;

class CompositeExpression implements ICompositeExpression, \Countable {
	/** @var \Doctrine\DBAL\Query\Expression\CompositeExpression */
	protected $compositeExpression;

	/**
	 * Constructor.
	 *
	 * @param \Doctrine\DBAL\Query\Expression\CompositeExpression $compositeExpression
	 */
	public function __construct(\Doctrine\DBAL\Query\Expression\CompositeExpression $compositeExpression) {
		$this->compositeExpression = $compositeExpression;
	}

	/**
	 * Adds multiple parts to composite expression.
	 *
	 * @param array $parts
	 *
	 * @return \OCP\DB\QueryBuilder\ICompositeExpression
	 */
	public function addMultiple(array $parts = []): ICompositeExpression {
		$this->compositeExpression->addMultiple($parts);

		return $this;
	}

	/**
	 * Adds an expression to composite expression.
	 *
	 * @param mixed $part
	 *
	 * @return \OCP\DB\QueryBuilder\ICompositeExpression
	 */
	public function add($part): ICompositeExpression {
		$this->compositeExpression->add($part);

		return $this;
	}

	/**
	 * Retrieves the amount of expressions on composite expression.
	 *
	 * @return integer
	 */
	public function count(): int {
		return $this->compositeExpression->count();
	}

	/**
	 * Returns the type of this composite expression (AND/OR).
	 *
	 * @return string
	 */
	public function getType(): string {
		return $this->compositeExpression->getType();
	}

	/**
	 * Retrieves the string representation of this composite expression.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return (string) $this->compositeExpression;
	}
}
