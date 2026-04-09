<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Image;
use OCP\ITempManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

abstract class Office extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function isAvailable(FileInfo $file): bool {
		return is_string($this->options['officeBinary']);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if (!$this->isAvailable($file)) {
			return null;
		}

		$tempManager = Server::get(ITempManager::class);

		// The file to generate the preview for.
		$absPath = $this->getLocalFile($file);
		if ($absPath === false) {
			Server::get(LoggerInterface::class)->error(
				'Failed to get local file to generate thumbnail for: ' . $file->getPath(),
				['app' => 'core']
			);
			return null;
		}

		// The destination for the LibreOffice user profile.
		// LibreOffice can rune once per user profile and therefore instance id and file id are included.
		$profile = $tempManager->getTemporaryFolder(
			'nextcloud-office-profile-' . \OC_Util::getInstanceId() . '-' . $file->getId()
		);

		// The destination for the LibreOffice convert result.
		$outdir = $tempManager->getTemporaryFolder(
			'nextcloud-office-preview-' . \OC_Util::getInstanceId() . '-' . $file->getId()
		);

		if ($profile === false || $outdir === false) {
			$this->cleanTmpFiles();
			return null;
		}

		$parameters = [
			$this->options['officeBinary'],
			'-env:UserInstallation=file://' . escapeshellarg($profile),
			'--headless',
			'--nologo',
			'--nofirststartwizard',
			'--invisible',
			'--norestore',
			'--convert-to png',
			'--outdir ' . escapeshellarg($outdir),
			escapeshellarg($absPath),
		];

		$cmd = implode(' ', $parameters);
		exec($cmd, $output, $returnCode);

		if ($returnCode !== 0) {
			$this->cleanTmpFiles();
			return null;
		}

		$preview = $outdir . pathinfo($absPath, PATHINFO_FILENAME) . '.png';

		$image = new Image();
		$image->loadFromFile($preview);

		$this->cleanTmpFiles();

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);
			return $image;
		}

		return null;
	}
}
