<?php
declare(strict_types=1);
namespace OC\Preview;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Image;

class CDR extends ProviderV2 {
	public function getMimeType(): string {
		return '/application\/coreldraw/';
	}
	public function isAvailable(FileInfo $file): bool {
		return $file->getSize() > 0;
	}
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if (!$this->isAvailable($file)) {
			return null;
		}

		$localFile = $this->getLocalFile($file);

		if ($localFile === false) {
			return null;
		}

		$data = $this->extractThumbnail($localFile);
		if ($data === null) {
			return null;
		}

		$image = new Image();
		$image->loadFromData($data);

		if (!$image->valid()) {
			return null;
		}

		$image->scaleDownToFit($maxX, $maxY);

		return $image;
	}
	/** * Extract ONLY thumbnail (no pages) */
	private function extractThumbnail(string $file): ?string {
		$zip = new \ZipArchive();

		if ($zip->open($file) !== true) {
			return null;
		}

		/** * CDR files created by newer CorelDRAW versions store the embedded preview in previews/thumbnail.png.
		    * OLD CDR format (BMP thumbnail) * metadata/thumbnails/thumbnail.bmp */
		foreach ([
					 'previews/thumbnail.png',
					 'metadata/thumbnails/thumbnail.bmp',
				 ] as $thumbnail) {
			$idx = $zip->locateName($thumbnail);

			if ($idx === false) {
				continue;
			}

			$data = $zip->getFromIndex($idx);
			$zip->close();

			return $data === false ? null : $data;
		}

		$zip->close();

		return null;
	}
}
