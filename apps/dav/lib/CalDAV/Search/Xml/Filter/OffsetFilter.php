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

class OffsetFilter implements XmlDeserializable {

	/**
	 * @param Reader $reader
	 * @throws BadRequest
	 * @return int
	 */
	public static function xmlDeserialize(Reader $reader) {
		$value = $reader->parseInnerTree();
		if (!is_int($value) && !is_string($value)) {
			throw new BadRequest('The {' . SearchPlugin::NS_Nextcloud . '}offset has illegal value');
		}

		return (int)$value;
	}
}
