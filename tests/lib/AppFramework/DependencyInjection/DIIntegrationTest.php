<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\DependencyInjection;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Config;
use OC\Server;
use Test\TestCase;

interface Interface1 {
}

class ClassA1 implements Interface1 {
}

class ClassA2 implements Interface1 {
}

class ClassB {
	/**
	 * ClassB constructor.
	 *
	 * @param Interface1 $interface1
	 */
	public function __construct(
		public Interface1 $interface1,
	) {
	}
}

class DIIntegrationTest extends TestCase {
	private DIContainer $container;
	private Server $server;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->server = new Server('', new Config(\OC::$configDir));
		$this->container = new DIContainer('App1', [], $this->server);
	}

	public function testInjectFromServer(): void {
		$this->server->registerService(Interface1::class, function () {
			return new ClassA1();
		});

		$this->server->registerService(ClassB::class, function (SimpleContainer $c) {
			return new ClassB(
				$c->get(Interface1::class)
			);
		});

		/** @var ClassB $res */
		$res = $this->container->get(ClassB::class);
		$this->assertSame(ClassA1::class, get_class($res->interface1));
	}

	public function testInjectDepFromServer(): void {
		$this->server->registerService(Interface1::class, function () {
			return new ClassA1();
		});

		$this->container->registerService(ClassB::class, function (SimpleContainer $c) {
			return new ClassB(
				$c->get(Interface1::class)
			);
		});

		/** @var ClassB $res */
		$res = $this->container->get(ClassB::class);
		$this->assertSame(ClassA1::class, get_class($res->interface1));
	}

	public function testOverwriteDepFromServer(): void {
		$this->server->registerService(Interface1::class, function () {
			return new ClassA1();
		});

		$this->container->registerService(Interface1::class, function () {
			return new ClassA2();
		});

		$this->container->registerService(ClassB::class, function (SimpleContainer $c) {
			return new ClassB(
				$c->get(Interface1::class)
			);
		});

		/** @var ClassB $res */
		$res = $this->container->get(ClassB::class);
		$this->assertSame(ClassA2::class, get_class($res->interface1));
	}

	public function testIgnoreOverwriteInServerClass(): void {
		$this->server->registerService(Interface1::class, function () {
			return new ClassA1();
		});

		$this->container->registerService(Interface1::class, function () {
			return new ClassA2();
		});

		$this->server->registerService(ClassB::class, function (SimpleContainer $c) {
			return new ClassB(
				$c->get(Interface1::class)
			);
		});

		/** @var ClassB $res */
		$res = $this->container->get(ClassB::class);
		$this->assertSame(ClassA1::class, get_class($res->interface1));
	}
}
