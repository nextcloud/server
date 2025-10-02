<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace SearchDAV\Query;

class Operator {
	const OPERATION_AND = '{DAV:}and';
	const OPERATION_OR = '{DAV:}or';
	const OPERATION_NOT = '{DAV:}not';
	const OPERATION_EQUAL = '{DAV:}eq';
	const OPERATION_LESS_THAN = '{DAV:}lt';
	const OPERATION_LESS_OR_EQUAL_THAN = '{DAV:}lte';
	const OPERATION_GREATER_THAN = '{DAV:}gt';
	const OPERATION_GREATER_OR_EQUAL_THAN = '{DAV:}gte';
	const OPERATION_IS_COLLECTION = '{DAV:}is-collection';
	const OPERATION_IS_DEFINED = '{DAV:}is-defined';
	const OPERATION_IS_LIKE = '{DAV:}like';
	const OPERATION_CONTAINS = '{DAV:}contains';

	/**
	 * @var string
	 *
	 * The type of operation, one of the Operator::OPERATION_* constants
	 */
	public $type;

	/**
	 * @var (Literal|\SearchDAV\Backend\SearchPropertyDefinition|Operator)[]
	 *
	 * The list of arguments for the operation
	 *
	 *  - SearchPropDefinition: property for comparison
	 *  - Literal: literal value for comparison
	 *  - Operator: nested operation for and/or/not operations
	 *
	 * Which type and what number of argument an Operator takes depends on the operator type.
	 */
	public $arguments;

	/**
	 * Operator constructor.
	 *
	 * @param string $type
	 * @param (Literal|\SearchDAV\Backend\SearchPropertyDefinition|Operator)[] $arguments
	 */
	public function __construct(string $type = '', array $arguments = []) {
		$this->type = $type;
		$this->arguments = $arguments;
	}
}
