<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV\Xml;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Groups implements XmlSerializable {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/** @var string[] of TYPE:CHECKSUM */
	private $groups;

	/**
	 * @param string $groups
	 */
	public function __construct($groups) {
		$this->groups = $groups;
	}

	public function xmlSerialize(Writer $writer) {
		foreach ($this->groups as $group) {
			$writer->writeElement('{' . self::NS_OWNCLOUD . '}group', $group);
		}
	}
}
