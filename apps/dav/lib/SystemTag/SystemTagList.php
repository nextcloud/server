<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\SystemTag;

use OCP\SystemTag\ISystemTag;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * TagList property
 *
 * This property writes the prebuilt "system-tag" element of each tag.
 */
class SystemTagList implements Element {
	/**
	 * @param list<ISystemTag> $tags
	 * @param list<string> $serializedTags the system-tag element of each tag in the same order, built for the writer this list is serialized with
	 */
	public function __construct(
		private array $tags,
		private array $serializedTags,
	) {
	}

	/**
	 * @return list<ISystemTag>
	 */
	public function getTags(): array {
		return $this->tags;
	}

	#[\Override]
	public static function xmlDeserialize(Reader $reader): void {
		// unsupported/unused
	}

	#[\Override]
	public function xmlSerialize(Writer $writer): void {
		foreach ($this->serializedTags as $serializedTag) {
			$writer->writeRaw($serializedTag);
		}
	}
}
