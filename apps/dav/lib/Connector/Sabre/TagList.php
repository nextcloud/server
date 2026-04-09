<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * TagList property
 *
 * This property contains multiple "tag" elements, each containing a tag name.
 */
class TagList implements Element {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/**
	 * @param array $tags
	 */
	public function __construct(
		/**
		 * tags
		 */
		private array $tags,
	) {
	}

	/**
	 * Returns the tags
	 *
	 * @return array
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * The deserialize method is called during xml parsing.
	 *
	 * This method is called statictly, this is because in theory this method
	 * may be used as a type of constructor, or factory method.
	 *
	 * Often you want to return an instance of the current class, but you are
	 * free to return other data as well.
	 *
	 * You are responsible for advancing the reader to the next element. Not
	 * doing anything will result in a never-ending loop.
	 *
	 * If you just want to skip parsing for this element altogether, you can
	 * just call $reader->next();
	 *
	 * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
	 * the next element.
	 *
	 * @param Reader $reader
	 * @return mixed
	 */
	public static function xmlDeserialize(Reader $reader) {
		$tags = [];

		$tree = $reader->parseInnerTree();
		if ($tree === null) {
			return null;
		}
		foreach ($tree as $elem) {
			if ($elem['name'] === '{' . self::NS_OWNCLOUD . '}tag') {
				$tags[] = $elem['value'];
			}
		}
		return new self($tags);
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
		foreach ($this->tags as $tag) {
			$writer->writeElement('{' . self::NS_OWNCLOUD . '}tag', $tag);
		}
	}
}
