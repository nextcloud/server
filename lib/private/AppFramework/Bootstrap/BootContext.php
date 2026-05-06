<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\IAppContainer;
use OCP\IServerContainer;

class BootContext implements IBootContext {
	public function __construct(
		private IAppContainer $appContainer,
	) {
	}

	#[\Override]
	public function getAppContainer(): IAppContainer {
		return $this->appContainer;
	}

	#[\Override]
	public function getServerContainer(): IServerContainer {
		return $this->appContainer->get(IServerContainer::class);
	}

	#[\Override]
	public function injectFn(callable $fn) {
		return (new FunctionInjector($this->appContainer))->injectFn($fn);
	}
}
