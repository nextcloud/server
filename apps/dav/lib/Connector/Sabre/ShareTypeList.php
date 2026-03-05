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
 * ShareTypeList property
 *
 * This property contains multiple "share-type" elements, each containing a share type.
 */
class ShareTypeList implements Element {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/**
	 * @param int[] $shareTypes
	 */
	public function __construct(
		/**
		 * Share types
		 */
		private $shareTypes,
	) {
	}

	/**
	 * Returns the share types
	 *
	 * @return int[]
	 */
	public function getShareTypes() {
		return $this->shareTypes;
	}

	/**
	 * The deserialize method is called during xml parsing.
	 *
	 * @param Reader $reader
	 * @return mixed
	 */
	public static function xmlDeserialize(Reader $reader) {
		$shareTypes = [];

		$tree = $reader->parseInnerTree();
		if ($tree === null) {
			return null;
		}
		foreach ($tree as $elem) {
			if ($elem['name'] === '{' . self::NS_OWNCLOUD . '}share-type') {
				$shareTypes[] = (int)$elem['value'];
			}
		}
		return new self($shareTypes);
	}

	/**
	 * The xmlSerialize method is called during xml writing.
	 *
	 * @param Writer $writer
	 * @return void
	 */
	public function xmlSerialize(Writer $writer) {
		foreach ($this->shareTypes as $shareType) {
			$writer->writeElement('{' . self::NS_OWNCLOUD . '}share-type', $shareType);
		}
	}
}
