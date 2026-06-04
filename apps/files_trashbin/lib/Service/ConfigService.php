<?php

declare(strict_types=1);

namespace OCA\Files_Trashbin\Service;

use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Server;

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class ConfigService {
	public static function getDeleteFromTrashEnabled(): bool {
		return Server::get(IConfig::class)->getSystemValueBool('files.trash.delete', true);
	}

	public static function injectInitialState(IInitialState $initialState): void {
		$initialState->provideLazyInitialState('config', function () {
			return [
				'allow_delete' => ConfigService::getDeleteFromTrashEnabled(),
			];
		});
	}
}
