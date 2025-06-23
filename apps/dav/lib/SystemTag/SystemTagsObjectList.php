<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\SystemTag;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;

/**
 * This property contains multiple "object-id" elements.
 */
class SystemTagsObjectList implements XmlSerializable, XmlDeserializable {

	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';
	public const OBJECTID_ROOT_PROPERTYNAME = '{http://nextcloud.org/ns}object-id';
	public const OBJECTID_PROPERTYNAME = '{http://nextcloud.org/ns}id';
	public const OBJECTTYPE_PROPERTYNAME = '{http://nextcloud.org/ns}type';

	/**
	 * @param array<string, string> $objects An array of object ids and their types
	 */
	public function __construct(
		private array $objects,
	) {
	}

	/**
	 * Get the object ids and their types.
	 *
	 * @return array<string, string>
	 */
	public function getObjects(): array {
		return $this->objects;
	}

	public static function xmlDeserialize(Reader $reader) {
		$tree = $reader->parseInnerTree();
		if ($tree === null) {
			return null;
		}

		$objects = [];
		foreach ($tree as $elem) {
			if ($elem['name'] === self::OBJECTID_ROOT_PROPERTYNAME) {
				$value = $elem['value'];
				$id = '';
				$type = '';
				foreach ($value as $subElem) {
					if ($subElem['name'] === self::OBJECTID_PROPERTYNAME) {
						$id = $subElem['value'];
					} elseif ($subElem['name'] === self::OBJECTTYPE_PROPERTYNAME) {
						$type = $subElem['value'];
					}
				}
				if ($id !== '' && $type !== '') {
					$objects[(string)$id] = (string)$type;
				}
			}
		}

		return new self($objects);
	}

	/**
	 * The xmlSerialize method is called during xml writing.
	 *
	 * @param Writer $writer
	 * @return void
	 */
	public function xmlSerialize(Writer $writer) {
		foreach ($this->objects as $objectsId => $type) {
			$writer->startElement(SystemTagPlugin::OBJECTIDS_PROPERTYNAME);
			$writer->writeElement(self::OBJECTID_PROPERTYNAME, $objectsId);
			$writer->writeElement(self::OBJECTTYPE_PROPERTYNAME, $type);
			$writer->endElement();
		}
	}
}
