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

use Sabre\Xml\ParseException;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;
use function Sabre\Xml\Deserializer\keyValue;

/**
 * The object representation of a search query made by the client
 */
class BasicSearch implements XmlDeserializable {
	/**
	 * @var string[]
	 *
	 * The list of properties to be selected, specified in clark notation
	 */
	public $select;
	/**
	 * @var Scope[]
	 *
	 * The collections to perform the search in
	 */
	public $from;
	/**
	 * @var ?Operator
	 *
	 * The search operator, either a comparison ('gt', 'eq', ...) or a boolean operator ('and', 'or', 'not')
	 */
	public $where;
	/**
	 * @var Order[]
	 *
	 * The list of order operations that should be used to order the results.
	 *
	 * Each order operations consists of a property to sort on and a sort direction.
	 * If more then one order operations are specified, the comparisons for ordering should
	 * be applied in the order that the order operations are defined in with the earlier comparisons being
	 * more significant.
	 */
	public $orderBy;
	/**
	 * @var Limit
	 *
	 * The limit and offset for the search query
	 */
	public $limit;

	public function __construct(array $select, array $from, ?Operator $where, array $orderBy, Limit $limit) {
		$this->select = $select;
		$this->from = $from;
		$this->where = $where;
		$this->orderBy = $orderBy;
		$this->limit = $limit;
	}


	/**
	 * @param Reader $reader
	 * @return BasicSearch
	 * @throws ParseException
	 */
	public static function xmlDeserialize(Reader $reader): BasicSearch {
		$elements = keyValue($reader);

		if (!isset($elements['{DAV:}from'])) {
			throw new ParseException('Missing {DAV:}from when parsing {DAV:}basicsearch');
		}

		return new BasicSearch(
			$elements['{DAV:}select'] ?? [],
			$elements['{DAV:}from'],
			$elements['{DAV:}where'] ?? null,
			$elements['{DAV:}orderby'] ?? [],
			$elements['{DAV:}limit'] ?? new Limit()
		);
	}
}
