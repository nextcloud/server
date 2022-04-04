<?php

namespace OC\Metadata\Provider;

use OC\Metadata\FileMetadata;
use OC\Metadata\IMetadataProvider;
use OCP\Files\File;

class ExifProvider implements IMetadataProvider {
	public static function groupsProvided(): array {
		return ['size'];
	}

	public static function isAvailable(): bool {
		return extension_loaded('exif');
	}

	public function execute(File $file): array {
		$fileDescriptor = $file->fopen('rb');
		$data = @exif_read_data($fileDescriptor, 'ANY_TAG', true);

		$size = new FileMetadata();
		$size->setGroupName('size');
		$size->setId($file->getId());
		$size->setMetadata([]);

		if (!$data) {
			return [
				'size' => $size,
			];
		}

		if (array_key_exists('COMPUTED', $data)
			&& array_key_exists('Width', $data['COMPUTED'])
			&& array_key_exists('Height', $data['COMPUTED'])
		) {
			$size->setMetadata([
				'width' => $data['COMPUTED']['Width'],
				'height' => $data['COMPUTED']['Height'],
			]);
		}

		return [
			'size' => $size,
		];
	}

	public static function getMimetypesSupported(): string {
		return '/image\/.*/';
	}
}
