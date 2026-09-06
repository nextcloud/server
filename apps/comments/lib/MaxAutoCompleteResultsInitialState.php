<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Comments;

use OCP\AppFramework\Services\InitialStateProvider;
use OCP\IAppConfig;
use OCP\IConfig;

class MaxAutoCompleteResultsInitialState extends InitialStateProvider {
	public function __construct(
		private IConfig $config,
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getKey(): string {
		return 'maxAutoCompleteResults';
	}

	#[\Override]
	public function getData(): int {
		return (int)$this->appConfig->getValue('comments', 'maxAutoCompleteResults', '10');
	}
}
