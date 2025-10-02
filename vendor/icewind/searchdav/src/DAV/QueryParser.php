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

namespace SearchDAV\DAV;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Service;
use SearchDAV\XML\BasicSearch;
use SearchDAV\XML\Limit;
use SearchDAV\XML\Literal;
use SearchDAV\XML\Operator;
use SearchDAV\XML\Order;
use SearchDAV\XML\Scope;
use function Sabre\Xml\Deserializer\keyValue;
use function Sabre\Xml\Deserializer\repeatingElements;

class QueryParser extends Service {
	public $namespaceMap = [
		'DAV:'                             => 'd',
		'http://sabredav.org/ns'           => 's',
		'http://www.w3.org/2001/XMLSchema' => 'xs',
		SearchPlugin::SEARCHDAV_NS         => 'sd'
	];

	public function __construct() {
		$this->elementMap = [
			'{DAV:}literal'                => Literal::class,
			'{DAV:}searchrequest'          => Element\KeyValue::class,
			'{DAV:}query-schema-discovery' => Element\KeyValue::class,
			'{DAV:}basicsearch'            => BasicSearch::class,
			'{DAV:}select'                 => function (Reader $reader) {
				return keyValue($reader, '{DAV:}scope')['{DAV:}prop'];
			},
			'{DAV:}from' => function (Reader $reader) {
				return repeatingElements($reader, '{DAV:}scope');
			},
			'{DAV:}orderby' => function (Reader $reader) {
				return repeatingElements($reader, '{DAV:}order');
			},
			'{DAV:}scope' => Scope::class,
			'{DAV:}where' => function (Reader $reader) {
				$operators = array_map(function ($element) {
					return $element['value'];
				}, $reader->parseGetElements());
				return (isset($operators[0])) ? $operators[0] : null;
			},
			'{DAV:}prop'          => Element\Elements::class,
			'{DAV:}order'         => Order::class,
			'{DAV:}eq'            => Operator::class,
			'{DAV:}gt'            => Operator::class,
			'{DAV:}gte'           => Operator::class,
			'{DAV:}lt'            => Operator::class,
			'{DAV:}lte'           => Operator::class,
			'{DAV:}and'           => Operator::class,
			'{DAV:}or'            => Operator::class,
			'{DAV:}like'          => Operator::class,
			'{DAV:}contains'      => Operator::class,
			'{DAV:}not'           => Operator::class,
			'{DAV:}is-collection' => Operator::class,
			'{DAV:}is-defined'    => Operator::class,
			'{DAV:}limit'         => Limit::class,
		];
	}
}
