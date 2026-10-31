<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files;

use OCA\Files\Service\ChunkedUploadConfig;
use OCP\Capabilities\ICapability;
use OCP\Files\Conversion\ConversionMimeProvider;
use OCP\Files\Conversion\IConversionManager;

class Capabilities implements ICapability {

	public function __construct(
		protected IConversionManager $fileConversionManager,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{bigfilechunking: bool, chunked_upload: array{max_size: int, max_parallel_count: int}, file_conversions: list<array{from: string, to: string, extension: string, displayName: string}>}}
	 */
	#[\Override]
	public function getCapabilities(): array {
		return [
			'files' => [
				'bigfilechunking' => true,
				'chunked_upload' => [
					'max_size' => ChunkedUploadConfig::getMaxChunkSize(),
					'max_parallel_count' => ChunkedUploadConfig::getMaxParallelCount(),
				],

				'file_conversions' => array_map(function (ConversionMimeProvider $mimeProvider) {
					return $mimeProvider->jsonSerialize();
				}, $this->fileConversionManager->getProviders()),
			],
		];
	}
}
