<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Migration;

use Closure;
use OCA\Files\Service\ChunkedUploadConfig;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version2003Date20241021095629 extends SimpleMigrationStep {
	public function __construct(
		public readonly IAppConfig $appConfig,
	) {
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$maxChunkSize = $this->appConfig->getValueInt('files', 'max_chunk_size');
		if ($maxChunkSize === 0) {
			// Skip if no value was configured before
			return;
		}

		ChunkedUploadConfig::setMaxChunkSize($maxChunkSize);
		$this->appConfig->deleteKey('files', 'max_chunk_size');
	}
}
