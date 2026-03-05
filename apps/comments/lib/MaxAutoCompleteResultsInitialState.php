<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments;

use OCP\AppFramework\Services\InitialStateProvider;
use OCP\IConfig;

class MaxAutoCompleteResultsInitialState extends InitialStateProvider {
	public function __construct(
		private IConfig $config,
	) {
	}

	public function getKey(): string {
		return 'maxAutoCompleteResults';
	}

	public function getData(): int {
		return (int)$this->config->getAppValue('comments', 'maxAutoCompleteResults', '10');
	}
}
