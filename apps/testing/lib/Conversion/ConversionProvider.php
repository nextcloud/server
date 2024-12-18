<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Conversion;

use OCP\Files\Conversion\ConversionMimeTuple;
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
			new ConversionMimeTuple('image/jpeg', [
				['mime' => 'image/png', 'name' => $this->l10n->t('Image (.png)')],
			])
		];
	}

	public function convertFile(File $file, string $targetMimeType): mixed {
		$image = imagecreatefromstring($file->getContent());

		imagepalettetotruecolor($image);

		ob_start();
		imagepng($image);
		return ob_get_clean();
	}
}
