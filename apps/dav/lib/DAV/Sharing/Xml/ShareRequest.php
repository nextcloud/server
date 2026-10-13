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
	public const ELEMENT_SHARE = '{' . Plugin::NS_OWNCLOUD . '}share';

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

	#[\Override]
	public static function xmlDeserialize(Reader $reader) {
		/**
		 * @var list<array{
		 *     name: ShareRequestElement::ELEMENT_SET|ShareRequestElement::ELEMENT_REMOVE,
		 *     value: array{
		 *         href: string,
		 *         commonName?: string|null,
		 *         readOnly?: bool
		 *     }
		 * }>|string|null $elements
		 */
		$elements = $reader->parseInnerTree([
			ShareRequestElement::ELEMENT_SET => ShareRequestElement::class,
			ShareRequestElement::ELEMENT_REMOVE => ShareRequestElement::class,
		]);

		$set = [];
		$remove = [];

		if (!is_array($elements)) {
			return new self($set, $remove);
		}

		foreach ($elements as $element) {
			switch ($element['name']) {
				case ShareRequestElement::ELEMENT_SET:
					$set[] = $element['value'];
					break;
				case ShareRequestElement::ELEMENT_REMOVE:
					$remove[] = $element['value']['href'];
					break;
			}
		}

		return new self($set, $remove);
	}
}
