<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Conversion;

use OCP\Files\Conversion\ConversionMimeProvider;
use OCP\Files\Conversion\IConversionProvider;
use OCP\Files\File;
use OCP\IL10N;

class ConversionProvider implements IConversionProvider {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getSupportedMimeTypes(): array {
		return [
			new ConversionMimeProvider('image/jpeg', 'image/png', 'png', $this->l10n->t('Image (.png)')),
			new ConversionMimeProvider('image/jpeg', 'image/gif', 'gif', $this->l10n->t('Image (.gif)')),
		];
	}

	public function convertFile(File $file, string $targetMimeType): mixed {
		$image = imagecreatefromstring($file->getContent());
		imagepalettetotruecolor($image);

		// Start output buffering
		ob_start();

		// Convert the image to the target format
		if ($targetMimeType === 'image/gif') {
			imagegif($image);
		} else {
			imagepng($image);
		}

		// End and return the output buffer
		return ob_get_clean();
	}
}
