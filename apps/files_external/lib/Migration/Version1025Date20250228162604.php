<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Migration;

use Closure;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\DB\ISchemaWrapper;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Check for any external storage overwriting the home folder
 */
class Version1025Date20250228162604 extends SimpleMigrationStep {
	public function __construct(
		private GlobalStoragesService $globalStoragesServices,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$this->globalStoragesServices->updateOverwriteHomeFolders();
	}
}
