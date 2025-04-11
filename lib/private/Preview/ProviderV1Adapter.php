<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\Files\View;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Preview\IProvider;
use OCP\Preview\IProviderV2;

class ProviderV1Adapter implements IProviderV2 {
	private $providerV1;

	public function __construct(IProvider $providerV1) {
		$this->providerV1 = $providerV1;
	}

	public function getMimeType(): string {
		return (string)$this->providerV1->getMimeType();
	}

	public function isAvailable(FileInfo $file): bool {
		return (bool)$this->providerV1->isAvailable($file);
	}

	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		[$view, $path] = $this->getViewAndPath($file);
		$thumbnail = $this->providerV1->getThumbnail($path, $maxX, $maxY, false, $view);
		return $thumbnail === false ? null: $thumbnail;
	}

	private function getViewAndPath(File $file) {
		$view = new View(dirname($file->getPath()));
		$path = $file->getName();

		return [$view, $path];
	}
}
