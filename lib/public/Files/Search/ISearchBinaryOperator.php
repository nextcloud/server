<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
