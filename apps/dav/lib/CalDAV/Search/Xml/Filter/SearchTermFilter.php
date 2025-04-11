<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Search\Xml\Filter;

use OCA\DAV\CalDAV\Search\SearchPlugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

class SearchTermFilter implements XmlDeserializable {

	/**
	 * @param Reader $reader
	 * @throws BadRequest
	 * @return string
	 */
	public static function xmlDeserialize(Reader $reader) {
		$value = $reader->parseInnerTree();
		if (!is_string($value)) {
			throw new BadRequest('The {' . SearchPlugin::NS_Nextcloud . '}search-term has illegal value');
		}

		return $value;
	}
}
