<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\Share\IShare;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This property contains multiple "sharee" elements, each containing a share sharee
 */
class ShareeList implements XmlSerializable {
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';

	/** @var IShare[] */
	private $shares;

	public function __construct(array $shares) {
		$this->shares = $shares;
	}

	/**
	 * The xmlSerialize method is called during xml writing.
	 *
	 * @param Writer $writer
	 * @return void
	 */
	public function xmlSerialize(Writer $writer) {
		foreach ($this->shares as $share) {
			$writer->startElement('{' . self::NS_NEXTCLOUD . '}sharee');
			$writer->writeElement('{' . self::NS_NEXTCLOUD . '}id', $share->getSharedWith());
			$writer->writeElement('{' . self::NS_NEXTCLOUD . '}display-name', $share->getSharedWithDisplayName());
			$writer->writeElement('{' . self::NS_NEXTCLOUD . '}type', $share->getShareType());
			$writer->endElement();
		}
	}
}
