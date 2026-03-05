<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Publishing\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Publisher implements XmlSerializable {

	/**
	 * @param string $publishUrl
	 * @param boolean $isPublished
	 */
	public function __construct(
		protected $publishUrl,
		protected $isPublished,
	) {
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->publishUrl;
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
		if (!$this->isPublished) {
			// for pre-publish-url
			$writer->write($this->publishUrl);
		} else {
			// for publish-url
			$writer->writeElement('{DAV:}href', $this->publishUrl);
		}
	}
}
