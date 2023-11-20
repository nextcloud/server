<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tor Lillqvist <tml@collabora.com>
 *
 * @license AGPL-3.0
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
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\ITempManager;
use OCP\Server;

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

		$image = new \OCP\Image();
		$image->loadFromFile($preview);

		$this->cleanTmpFiles();

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);
			return $image;
		}

		return null;
	}
}
