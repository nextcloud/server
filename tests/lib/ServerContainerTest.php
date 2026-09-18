<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\ServerContainer;

class ServerContainerTest extends TestCase {
	public function testResetForNextRequestCascadesIntoRegisteredAppContainers(): void {
		$container = new ServerContainer();

		$appContainer = $this->createMock(DIContainer::class);
		$appContainer->expects($this->once())
			->method('resetForNextRequest');

		$container->registerAppContainer('testapp', $appContainer);

		$container->resetForNextRequest();
	}
}
