<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\AppFramework\Bootstrap\BootContext;
use OC\Server;
use OCP\AppFramework\IAppContainer;
use Test\TestCase;

class BootContextTest extends TestCase {
	private BootContext $context;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->context = $this->createInstanceWithMocks(BootContext::class);
	}

	public function testGetAppContainer(): void {
		$container = $this->context->getAppContainer();

		$this->assertSame($this->mocks[IAppContainer::class], $container);
	}

	public function testGetServerContainer(): void {
		$container = $this->context->getServerContainer();

		$this->assertSame($this->mocks[Server::class], $container);
	}
}
