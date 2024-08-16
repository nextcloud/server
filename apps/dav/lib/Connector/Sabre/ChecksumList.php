<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Checksumlist property
 *
 * This property contains multiple "checksum" elements, each containing a
 * checksum name.
 */
class ChecksumList implements XmlSerializable {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/** @var string[] of TYPE:CHECKSUM */
	private array $checksums;

	public function __construct(string $checksum) {
		$this->checksums = explode(' ', $checksum);
	}

	/**
	 * The xmlSerialize method is called during xml writing.
	 *
	 * Use the $writer argument to write its own xml serialization.
	 *
	 * An important note: do _not_ create a parent element. Any element
	 * implementing XmlSerializble should only ever write what's considered
	 * its 'inner xml'.
	 *
	 * The parent of the current element is responsible for writing a
	 * containing element.
	 *
	 * This allows serializers to be re-used for different element names.
	 *
	 * If you are opening new elements, you must also close them again.
	 *
	 * @param Writer $writer
	 * @return void
	 */
	public function xmlSerialize(Writer $writer) {
		foreach ($this->checksums as $checksum) {
			$writer->writeElement('{' . self::NS_OWNCLOUD . '}checksum', $checksum);
		}
	}
}
