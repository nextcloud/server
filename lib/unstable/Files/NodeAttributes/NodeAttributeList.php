<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Files\NodeAttributes;

/**
 * A list value of a node attribute.
 *
 * Over WebDAV each value is a child element named `$childName` in the namespace
 * of the attribute, e.g. `<oc:share-types><oc:share-type>0</oc:share-type></oc:share-types>`.
 * A value can also be a record, written as one element per field.
 * As JSON it has the shape the WebDAV client produces: `{ "share-type": [0] }`.
 *
 * @experimental 36.0.0
 */
final class NodeAttributeList implements \JsonSerializable {
	/**
	 * @param string $childName local name of the child elements
	 * @param list<bool|int|float|string|array<string, bool|int|float|string|null>> $values
	 * @experimental 36.0.0
	 */
	public function __construct(
		private readonly string $childName,
		private readonly array $values,
	) {
	}

	/**
	 * @experimental 36.0.0
	 */
	public function getChildName(): string {
		return $this->childName;
	}

	/**
	 * @return list<bool|int|float|string|array<string, bool|int|float|string|null>>
	 * @experimental 36.0.0
	 */
	public function getValues(): array {
		return $this->values;
	}

	/**
	 * @return array<string, list<bool|int|float|string|array<string, bool|int|float|string|null>>>
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [$this->childName => $this->values];
	}
}
