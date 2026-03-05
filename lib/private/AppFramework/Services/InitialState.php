<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Services;

use OCP\AppFramework\Services\IInitialState;
use OCP\IInitialStateService;

class InitialState implements IInitialState {
	public function __construct(
		private IInitialStateService $state,
		private string $appName,
	) {
	}

	public function provideInitialState(string $key, $data): void {
		$this->state->provideInitialState($this->appName, $key, $data);
	}

	public function provideLazyInitialState(string $key, \Closure $closure): void {
		$this->state->provideLazyInitialState($this->appName, $key, $closure);
	}
}
