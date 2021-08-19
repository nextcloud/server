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

namespace SearchDAV\Backend;

class SearchPropertyDefinition {
	const XS = '{http://www.w3.org/2001/XMLSchema}';
	const DATATYPE_STRING = self::XS . 'string';
	const DATATYPE_INTEGER = self::XS . 'integer';
	const DATATYPE_NONNEGATIVE_INTEGER = self::XS . 'nonNegativeInteger';
	const DATATYPE_DECIMAL = self::XS . 'decimal';
	const DATATYPE_DATETIME = self::XS . 'dateTime';
	const DATATYPE_BOOLEAN = self::XS . 'boolean';


	/** @var boolean */
	public $searchable;
	/** @var boolean */
	public $selectable;
	/** @var boolean */
	public $sortable;
	/** @var boolean */
	public $caseSensitive;
	/** @var string */
	public $dataType;
	/** @var string */
	public $name;

	/**
	 * SearchProperty constructor.
	 *
	 * @param string $name the name and namespace of the property in clark notation
	 * @param bool $searchable whether or not this property can be used as part of a search query
	 * @param bool $selectable whether or not this property can be returned as part of a search result
	 * @param bool $sortable whether or not this property can be used to sort the search result
	 * @param string $dataType the datatype of the property, one of the SearchProperty::DATATYPE_ constants or any XSD datatype in clark notation
	 * @param bool $caseSensitive whether or not comparisons on the property are case sensitive, only applies to string propertries
	 */
	public function __construct($name, $searchable, $selectable, $sortable, $dataType = self::DATATYPE_STRING, $caseSensitive = true) {
		$this->searchable = $searchable;
		$this->selectable = $selectable;
		$this->sortable = $sortable;
		$this->dataType = $dataType;
		$this->name = $name;
		$this->caseSensitive = $caseSensitive;
	}
}
