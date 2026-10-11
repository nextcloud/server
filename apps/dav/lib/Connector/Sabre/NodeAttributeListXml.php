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
	 * @param list<bool|int|float|string|array<string, bool|int|float|string|null>> $values
	 */
	public function __construct(
		private readonly string $childElement,
		private readonly array $values,
	) {
	}

	#[\Override]
	public function xmlSerialize(Writer $writer): void {
		$namespace = substr($this->childElement, 0, (int)strrpos($this->childElement, '}') + 1);
		foreach ($this->values as $value) {
			if (!is_array($value)) {
				$writer->writeElement($this->childElement, self::toText($value));
				continue;
			}

			$writer->startElement($this->childElement);
			foreach ($value as $field => $fieldValue) {
				$writer->writeElement($namespace . $field, $fieldValue === null ? null : self::toText($fieldValue));
			}
			$writer->endElement();
		}
	}

	private static function toText(bool|int|float|string $value): string {
		return is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
	}
}
