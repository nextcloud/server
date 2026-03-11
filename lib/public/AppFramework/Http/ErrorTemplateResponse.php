<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http;

use OC\App\AppManager;
use OCP\AppFramework\Http;
use OCP\Server;

class ErrorTemplateResponse extends TemplateResponse {
	public function __construct(
		string $errorMessage,
		string $hint,
		string $renderAs = self::RENDER_AS_USER,
		int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
		array $headers = []
	) {
		if ($errorMessage === $hint) {
			// If the hint is the same as the message there is no need to display it twice.
			$hint = '';
		}
		$errors = [['error' => $errorMessage, 'hint' => $hint]];
		$params = ['errors' => $errors];


		$appManager = Server::get(AppManager::class);
		if ($appManager->isEnabledForUser('theming') && !$appManager->isAppLoaded('theming')) {
			$appManager->loadApp('theming');
		}

		parent::__construct('', 'error', $params, $renderAs, $status, $headers);
	}
}
