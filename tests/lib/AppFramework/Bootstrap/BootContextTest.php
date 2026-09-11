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
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BootContextTest extends TestCase {
	private IAppContainer&MockObject $appContainer;
	private Server&MockObject $server;

	private BootContext $context;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->appContainer = $this->createMock(IAppContainer::class);

		$this->context = new BootContext(
			$this->server,
			$this->appContainer,
		);
	}

	public function testGetAppContainer(): void {
		$container = $this->context->getAppContainer();

		$this->assertSame($this->appContainer, $container);
	}

	public function testGetServerContainer(): void {
		$container = $this->context->getServerContainer();

		$this->assertSame($this->server, $container);
	}
}
