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
use SearchDAV\DAV\SearchPlugin;
use function Sabre\Xml\Deserializer\keyValue;

/**
 * The limit and offset of a search query
 */
class Limit extends \SearchDAV\Query\Limit implements XmlDeserializable {
	public static function xmlDeserialize(Reader $reader): Limit {
		$limit = new self();

		$elements = keyValue($reader);
		$namespace = SearchPlugin::SEARCHDAV_NS;

		$limit->maxResults = isset($elements['{DAV:}nresults']) ? $elements['{DAV:}nresults'] : 0;
		$firstResult = '{' . $namespace . '}firstresult';
		$limit->firstResult = isset($elements[$firstResult]) ? $elements[$firstResult] : 0;

		return $limit;
	}
}
