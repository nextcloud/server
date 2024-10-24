<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Migration;

use Closure;
use OCA\Files\Service\ChunkedUploadConfig;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Server;

class Version2003Date20241021095629 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$maxChunkSize = Server::get(IConfig::class)->getAppValue('files', 'max_chunk_size');
		if ($maxChunkSize === '') {
			// Skip if no value was configured before
			return;
		}

		ChunkedUploadConfig::setMaxChunkSize((int)$maxChunkSize);
		Server::get(IConfig::class)->deleteAppValue('files', 'max_chunk_size');
	}
}
