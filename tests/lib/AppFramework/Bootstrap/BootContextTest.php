<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\AppFramework\Bootstrap\BootContext;
use OCP\AppFramework\IAppContainer;
use OCP\IServerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BootContextTest extends TestCase {
	/** @var IAppContainer|MockObject */
	private $appContainer;

	/** @var BootContext */
	private $context;

	protected function setUp(): void {
		parent::setUp();

		$this->appContainer = $this->createMock(IAppContainer::class);

		$this->context = new BootContext(
			$this->appContainer
		);
	}

	public function testGetAppContainer(): void {
		$container = $this->context->getAppContainer();

		$this->assertSame($this->appContainer, $container);
	}

	public function testGetServerContainer(): void {
		$serverContainer = $this->createMock(IServerContainer::class);
		$this->appContainer->method('get')
			->with(IServerContainer::class)
			->willReturn($serverContainer);

		$container = $this->context->getServerContainer();

		$this->assertSame($serverContainer, $container);
	}
}
