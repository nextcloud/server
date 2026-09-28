<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\AppFramework\Bootstrap\FunctionInjector;
use OC\AppFramework\Utility\SimpleContainer;
use OCP\AppFramework\QueryException;
use Test\TestCase;

interface Foo {
}

class FunctionInjectorTest extends TestCase {
	/** @var SimpleContainer */
	private $container;

	protected function setUp(): void {
		parent::setUp();

		$this->container = new SimpleContainer();
	}

	public function testInjectFnNotRegistered(): void {
		$this->expectException(QueryException::class);

		(new FunctionInjector($this->container))->injectFn(static function (Foo $p1): void {
		});
	}

	public function testInjectFnNotRegisteredButNullable(): void {
		(new FunctionInjector($this->container))->injectFn(static function (?Foo $p1): void {
		});

		// Nothing to assert. No errors means everything is fine.
		$this->addToAssertionCount(1);
	}

	public function testInjectFnByType(): void {
		$this->container->registerService(Foo::class, function () {
			$this->addToAssertionCount(1);
			return new class implements Foo {
			};
		});

		(new FunctionInjector($this->container))->injectFn(static function (Foo $p1): void {
		});

		// Nothing to assert. No errors means everything is fine.
		$this->addToAssertionCount(1);
	}

	public function testInjectFnByName(): void {
		$this->container->registerParameter('test', 'abc');

		(new FunctionInjector($this->container))->injectFn(static function ($test): void {
		});

		// Nothing to assert. No errors means everything is fine.
		$this->addToAssertionCount(1);
	}
}
