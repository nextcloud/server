<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @copyright Copyright 2022 Louis Chmn <louis@chmn.me>
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\FilesMetadata\Provider;

use DateTime;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\FilesMetadata\Event\MetadataLiveEvent;

class OriginalDateTimeMetadataProvider implements IEventListener {
	public function __construct() {}

	public function handle(Event $event): void {
		if (!($event instanceof MetadataLiveEvent)) {
			return;
		}

		$node = $event->getNode();

		if (!$node instanceof File) {
			return;
		}

		$metadata = $event->getMetadata();

		if (!$metadata->hasKey('files-exif')) {
			return;
		}

		if (!array_key_exists('DateTimeOriginal', $metadata->getArray('files-exif'))) {
			return;
		}

		$rawDateTimeOriginal = $metadata->getArray('files-exif')['DateTimeOriginal'];
		$dateTimeOriginal = DateTime::createFromFormat("Y:m:d G:i:s", $rawDateTimeOriginal);
		$metadata->setInt('files-original_date_time', $dateTimeOriginal->getTimestamp(), true);
	}
}
