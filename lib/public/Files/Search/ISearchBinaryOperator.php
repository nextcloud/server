<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Search;

/**
 * @since 12.0.0
 */
interface ISearchBinaryOperator extends ISearchOperator {
	/**
	 * @since 12.0.0
	 */
	public const OPERATOR_AND = 'and';

	/**
	 * @since 12.0.0
	 */
	public const OPERATOR_OR = 'or';

	/**
	 * @since 12.0.0
	 */
	public const OPERATOR_NOT = 'not';

	/**
	 * The type of binary operator
	 *
	 * One of the ISearchBinaryOperator::OPERATOR_* constants
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getType();

	/**
	 * The arguments for the binary operator
	 *
	 * One argument for the 'not' operator and two for 'and' and 'or'
	 *
	 * @return ISearchOperator[]
	 * @since 12.0.0
	 */
	public function getArguments();
}
