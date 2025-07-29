<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair\NC16;

use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class CleanupCardDAVPhotoCache
 *
 * This repair step removes "photo." files created by photocache
 *
 * Before https://github.com/nextcloud/server/pull/13843 a "photo." file could be created
 * for unsupported image formats by photocache. Because a file is present but not jpg, png or gif no
 * photo could be returned for this vcard. These invalid files are removed by this migration step.
 */
class CleanupCardDAVPhotoCache implements IRepairStep {
	public function __construct(
		private IConfig $config,
		private IAppDataFactory $appDataFactory,
		private LoggerInterface $logger,
	) {
	}

	public function getName(): string {
		return 'Cleanup invalid photocache files for carddav';
	}

	private function repair(IOutput $output): void {
		$photoCacheAppData = $this->appDataFactory->get('dav-photocache');

		try {
			$folders = $photoCacheAppData->getDirectoryListing();
		} catch (NotFoundException $e) {
			return;
		} catch (RuntimeException $e) {
			$this->logger->error('Failed to fetch directory listing in CleanupCardDAVPhotoCache', ['exception' => $e]);
			return;
		}

		$folders = array_filter($folders, function (ISimpleFolder $folder) {
			return $folder->fileExists('photo.');
		});

		if (empty($folders)) {
			return;
		}

		$output->info('Delete ' . count($folders) . ' "photo." files');

		foreach ($folders as $folder) {
			try {
				/** @var ISimpleFolder $folder */
				$folder->getFile('photo.')->delete();
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				$output->warning('Could not delete file "dav-photocache/' . $folder->getName() . '/photo."');
			}
		}
	}

	private function shouldRun(): bool {
		return version_compare(
			$this->config->getSystemValueString('version', '0.0.0.0'),
			'16.0.0.0',
			'<='
		);
	}

	public function run(IOutput $output): void {
		if ($this->shouldRun()) {
			$this->repair($output);
		}
	}
}
