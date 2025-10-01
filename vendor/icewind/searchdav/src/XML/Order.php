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
use function Sabre\Xml\Deserializer\keyValue;

class Order implements XmlDeserializable {
	/**
	 * @var string
	 *
	 * The property that should be sorted on.
	 */
	public $property;
	/**
	 * @var string 'ascending' or 'descending'
	 *
	 * The sort direction
	 */
	public $order;

	/**
	 * Order constructor.
	 *
	 * @param string $property
	 * @param string $order
	 */
	public function __construct(string $property = '', string $order = \SearchDAV\Query\Order::ASC) {
		$this->property = $property;
		$this->order = $order;
	}

	public static function xmlDeserialize(Reader $reader): Order {
		$order = new self();

		$childs = keyValue($reader);

		$order->order = array_key_exists('{DAV:}descending', $childs) ? \SearchDAV\Query\Order::DESC : \SearchDAV\Query\Order::ASC;
		$order->property = $childs['{DAV:}prop'][0];

		return $order;
	}
}
