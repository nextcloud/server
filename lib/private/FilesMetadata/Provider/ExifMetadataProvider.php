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

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use Psr\Log\LoggerInterface;

// Extract EXIF, IFD0, and GPS data from a picture file.
// EXIF data reference: https://web.archive.org/web/20220428165430/exif.org/Exif2-2.PDF
class ExifMetadataProvider implements IEventListener {
	public function __construct(
		private LoggerInterface $logger
	) {}

	public function handle(Event $event): void {
		if (!($event instanceof MetadataLiveEvent)) {
			return;
		}

		$node = $event->getNode();

		if (!$node instanceof File) {
			return;
		}

		if (!preg_match('/image\/(png|jpeg|heif|webp|tiff)/', $node->getMimetype())) {
			return;
		}

		if (!extension_loaded('exif')) {
			return;
		}

		$fileDescriptor = $node->fopen('rb');
		if ($fileDescriptor === false) {
			return;
		}

		$rawExifData = null;

		try {
			// HACK: The stream_set_chunk_size call is needed to make reading exif data reliable.
			// This is to trigger this condition: https://github.com/php/php-src/blob/d64aa6f646a7b5e58359dc79479860164239580a/main/streams/streams.c#L710
			// But I don't understand yet why 1 as a special meaning.
			$oldBufferSize = stream_set_chunk_size($fileDescriptor, 1);
			$rawExifData = @exif_read_data($fileDescriptor, 'EXIF, GPS', true);
			// We then revert the change after having read the exif data.
			stream_set_chunk_size($fileDescriptor, $oldBufferSize);
		} catch (\Exception $ex) {
			$this->logger->info("Failed to extract metadata for " . $node->getId(), ['exception' => $ex]);
		}

		if ($rawExifData && array_key_exists('EXIF', $rawExifData)) {
			$event->getMetadata()->setArray('files-exif', $rawExifData['EXIF']);
		}

		if ($rawExifData && array_key_exists('IFD0', $rawExifData)) {
			$event->getMetadata()->setArray('files-ifd0', $rawExifData['IFD0']);
		}

		if (
			$rawExifData &&
			array_key_exists('GPS', $rawExifData) &&
			array_key_exists('GPSLatitude', $rawExifData['GPS']) && array_key_exists('GPSLatitudeRef', $rawExifData['GPS']) &&
			array_key_exists('GPSLongitude', $rawExifData['GPS']) && array_key_exists('GPSLongitudeRef', $rawExifData['GPS'])
		) {
			$event->getMetadata()->setArray('files-gps', [
				'latitude' => $this->gpsDegreesToDecimal($rawExifData['GPS']['GPSLatitude'], $rawExifData['GPS']['GPSLatitudeRef']),
				'longitude' => $this->gpsDegreesToDecimal($rawExifData['GPS']['GPSLongitude'], $rawExifData['GPS']['GPSLongitudeRef']),
				'altitude' => ($rawExifData['GPS']['GPSAltitudeRef'] === "\u{0000}" ? 1 : -1) * $this->parseGPSData($rawExifData['GPS']['GPSAltitude']),
			]);
		}
	}

	/**
	 * @param array|string $coordinates
	 */
	private function gpsDegreesToDecimal($coordinates, ?string $hemisphere): float {
		if (is_string($coordinates)) {
			$coordinates = array_map("trim", explode(",", $coordinates));
		}

		if (count($coordinates) !== 3) {
			throw new \Exception('Invalid coordinate format: ' . json_encode($coordinates));
		}

		[$degrees, $minutes, $seconds] = array_map(fn ($rawDegree) => $this->parseGPSData($rawDegree), $coordinates);

		$sign = ($hemisphere === 'W' || $hemisphere === 'S') ? -1 : 1;
		return $sign * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	private function parseGPSData(string $rawData): float {
		$parts = explode('/', $rawData);

		if ($parts[1] === '0') {
			return 0;
		}

		return floatval($parts[0]) / floatval($parts[1] ?? 1);
	}
}
