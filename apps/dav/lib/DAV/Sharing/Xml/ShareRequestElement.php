<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\DAV\Sharing\Xml;

use OCA\DAV\DAV\Sharing\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

class ShareRequestElement implements XmlDeserializable {
	public const ELEMENT_SET = '{' . Plugin::NS_OWNCLOUD . '}set';
	public const ELEMENT_REMOVE = '{' . Plugin::NS_OWNCLOUD . '}remove';

	private const ELEMENT_HREF = '{DAV:}href';
	private const ELEMENT_COMMON = '{' . Plugin::NS_OWNCLOUD . '}common-name';
	private const ELEMENT_READ_WRITE = '{' . Plugin::NS_OWNCLOUD . '}read-write';

	#[\Override]
	public static function xmlDeserialize(Reader $reader): array {
		$clark = $reader->getClark();
		$values = KeyValue::xmlDeserialize($reader);

		$href = $values[self::ELEMENT_HREF] ?? null;
		if (!is_string($href) || $href === '') {
			throw new BadRequest($clark . ' requires a href element');
		}

		if ($clark === self::ELEMENT_SET) {
			$commonName = $values[self::ELEMENT_COMMON] ?? null;
			if ($commonName !== null && !is_string($commonName)) {
				throw new BadRequest($clark . ' requires common-name to be text');
			}

			return [
				'href' => $href,
				'commonName' => $commonName,
				'readOnly' => !array_key_exists(self::ELEMENT_READ_WRITE, $values),
			];
		}

		if ($clark === self::ELEMENT_REMOVE) {
			return [
				'href' => $href,
			];
		}

		throw new BadRequest($clark . ' is not supported');
	}
}
