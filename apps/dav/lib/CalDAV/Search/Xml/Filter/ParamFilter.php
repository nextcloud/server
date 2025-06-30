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

class ParamFilter implements XmlDeserializable {

	/**
	 * @param Reader $reader
	 * @throws BadRequest
	 * @return string
	 */
	public static function xmlDeserialize(Reader $reader) {
		$att = $reader->parseAttributes();
		$property = $att['property'];
		$parameter = $att['name'];

		$reader->parseInnerTree();

		if (!is_string($property)) {
			throw new BadRequest('The {' . SearchPlugin::NS_Nextcloud . '}param-filter requires a valid property attribute');
		}
		if (!is_string($parameter)) {
			throw new BadRequest('The {' . SearchPlugin::NS_Nextcloud . '}param-filter requires a valid parameter attribute');
		}

		return [
			'property' => $property,
			'parameter' => $parameter,
		];
	}
}
