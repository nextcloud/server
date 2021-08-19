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


use Sabre\DAV\Xml\Element\Response;
use Sabre\Xml\Writer;

class QueryDiscoverResponse extends Response {
	/**
	 * @var BasicSearchSchema|null
	 */
	protected $schema;

	/**
	 * QueryDiscoverResponse constructor.
	 *
	 * @param string $href
	 * @param BasicSearchSchema|null $schema
	 * @param null|int|string $httpStatus
	 */
	function __construct($href, BasicSearchSchema $schema = null, $httpStatus = null) {
		parent::__construct($href, [], $httpStatus);
		$this->schema = $schema;

	}

	function xmlSerialize(Writer $writer) {
		if ($status = $this->getHTTPStatus()) {
			$writer->writeElement('{DAV:}status', 'HTTP/1.1 ' . $status . ' ' . \Sabre\HTTP\Response::$statusCodes[$status]);
		}
		$writer->writeElement('{DAV:}href', \Sabre\HTTP\encodePath($this->getHref()));

		if ($this->schema) {
			$writer->writeElement('{DAV:}query-schema', [
 				'{DAV:}basicsearchschema' => $this->schema
			]);
		}
	}
}
