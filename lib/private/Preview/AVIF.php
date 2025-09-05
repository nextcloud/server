<?php

declare(strict_types=1);

namespace OC\Preview;

use OCP\Files\FileInfo;

class AVIF extends Image {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/image\/avif/';
	}

	public function isAvailable(FileInfo $file): bool {
		return (bool)(imagetypes() & IMG_AVIF);
	}
}
