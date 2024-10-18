<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\SystemTag;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This property contains multiple "object-id" elements.
 */
class SystemTagsObjectList implements XmlSerializable {
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';

	/**
	 * @param array<string, string> $objects An array of object ids and their types
	 */
	public function __construct(
		private array $objects,
	) {	}

	/**
	 * The xmlSerialize method is called during xml writing.
	 *
	 * @param Writer $writer
	 * @return void
	 */
	public function xmlSerialize(Writer $writer) {
		foreach ($this->objects as $objectsId => $type) {
			$writer->startElement('{' . self::NS_NEXTCLOUD . '}object-id');
			$writer->writeElement('{' . self::NS_NEXTCLOUD . '}id', $objectsId);
			$writer->writeElement('{' . self::NS_NEXTCLOUD . '}type', $type);
			$writer->endElement();
		}
	}
}
