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
 * This property contains multiple "tag" elements, each containing a tag name.
 */
class SystemTagList implements Element {
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';

	/**
	 * @param list<ISystemTag> $tags
	 * @param array<int|string, string> $serializedTags system-tag element per tag id, for the writer this list is serialized with
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
		foreach ($this->tags as $tag) {
			$writer->writeRaw($this->serializedTags[$tag->getId()]);
		}
	}
}
