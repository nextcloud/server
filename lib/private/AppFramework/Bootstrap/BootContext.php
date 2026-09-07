<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use OC\Server;
use OCP\AppFramework\Bootstrap\IBootContext;
use Psr\Container\ContainerInterface;

class BootContext implements IBootContext {
	public function __construct(
		private Server $serverContainer,
		private ContainerInterface $appContainer,
	) {
	}

	#[\Override]
	public function getAppContainer(): ContainerInterface {
		return $this->appContainer;
	}

	#[\Override]
	public function getServerContainer(): ContainerInterface {
		return $this->serverContainer;
	}

	#[\Override]
	public function injectFn(callable $fn) {
		return (new FunctionInjector($this->appContainer))->injectFn($fn);
	}
}
