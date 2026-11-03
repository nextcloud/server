<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * A node attribute list as WebDAV property: one child element per value
 */
class NodeAttributeListXml implements XmlSerializable {
	/**
	 * @param string $childElement child element name in Clark notation
	 * @param list<bool|int|float|string> $values
	 */
	public function __construct(
		private readonly string $childElement,
		private readonly array $values,
	) {
	}

	#[\Override]
	public function xmlSerialize(Writer $writer): void {
		foreach ($this->values as $value) {
			$writer->writeElement($this->childElement, is_bool($value) ? ($value ? 'true' : 'false') : (string)$value);
		}
	}
}
