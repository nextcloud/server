<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\DAV\Sharing\Xml;

use OCA\DAV\DAV\Sharing\Plugin;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

class ShareRequest implements XmlDeserializable {
	/**
	 * Constructor
	 *
	 * @param array $set
	 * @param array $remove
	 */
	public function __construct(
		public array $set,
		public array $remove,
	) {
	}

	public static function xmlDeserialize(Reader $reader) {
		$elements = $reader->parseInnerTree([
			'{' . Plugin::NS_OWNCLOUD . '}set' => 'Sabre\\Xml\\Element\\KeyValue',
			'{' . Plugin::NS_OWNCLOUD . '}remove' => 'Sabre\\Xml\\Element\\KeyValue',
		]);

		$set = [];
		$remove = [];

		foreach ($elements as $elem) {
			switch ($elem['name']) {

				case '{' . Plugin::NS_OWNCLOUD . '}set':
					$sharee = $elem['value'];

					$sumElem = '{' . Plugin::NS_OWNCLOUD . '}summary';
					$commonName = '{' . Plugin::NS_OWNCLOUD . '}common-name';

					$set[] = [
						'href' => $sharee['{DAV:}href'],
						'commonName' => $sharee[$commonName] ?? null,
						'summary' => $sharee[$sumElem] ?? null,
						'readOnly' => !array_key_exists('{' . Plugin::NS_OWNCLOUD . '}read-write', $sharee),
					];
					break;

				case '{' . Plugin::NS_OWNCLOUD . '}remove':
					$remove[] = $elem['value']['{DAV:}href'];
					break;

			}
		}

		return new self($set, $remove);
	}
}
