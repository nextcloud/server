<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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

namespace SearchDAV\XML;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;
use SearchDAV\Query\Operator as QueryOperator;

class Operator implements XmlDeserializable {
	/**
	 * @var string
	 *
	 * The type of operation, one of the Operator::OPERATION_* constants
	 */
	public $type;
	/**
	 * @var (Literal|string|Operator)[]
	 *
	 * The list of arguments for the operation
	 *
	 *  - string: property name for comparison
	 *  - Literal: literal value for comparison
	 *  - Operation: nested operation for and/or/not operations
	 *
	 * Which type and what number of argument an Operator takes depends on the operator type.
	 */
	public $arguments;

	/**
	 * Operator constructor.
	 *
	 * @param string $type
	 * @param (Literal|string|Operator)[] $arguments
	 */
	public function __construct(string $type = '', array $arguments = []) {
		$this->type = $type;
		$this->arguments = $arguments;
	}

	public static function xmlDeserialize(Reader $reader): Operator {
		$operator = new self();

		$operator->type = $reader->getClark() ?? '';
		if ($reader->isEmptyElement) {
			$reader->next();
			return $operator;
		}

		if ($operator->type === QueryOperator::OPERATION_CONTAINS) {
			$operator->arguments[] = $reader->readString();
			$reader->next();
			return $operator;
		}

		$reader->read();
		do {
			if ($reader->nodeType === Reader::ELEMENT) {
				$argument = $reader->parseCurrentElement();
				if ($argument['name'] === '{DAV:}prop') {
					$operator->arguments[] = $argument['value'][0] ?? '';
				} else {
					$operator->arguments[] = $argument['value'];
				}
			} else {
				if (!$reader->read()) {
					break;
				}
			}
		} while ($reader->nodeType !== Reader::END_ELEMENT);

		$reader->read();

		return $operator;
	}
}
