<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Hooks;

use OC\Hooks\BasicEmitter;
use OC\Hooks\Emitter;

// class to make BasicEmitter::emit publicly available
class DummyEmitter extends BasicEmitter {
	public function emitEvent($scope, $method, $arguments = []): void {
		$this->emit($scope, $method, $arguments);
	}
}

// a dummy exception so we can check if an event is emitted
class EmittedException extends \Exception {
}

class BasicEmitterTest extends \Test\TestCase {
	/**
	 * @var Emitter
	 */
	protected $emitter;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->emitter = new DummyEmitter();
	}

	public function nonStaticCallBack(): void {
		throw new EmittedException;
	}

	public static function staticCallBack(): void {
		throw new EmittedException;
	}

	public function testAnonymousFunction(): void {
		$this->expectException(EmittedException::class);

		$this->emitter->listen('Test', 'test', function (): void {
			throw new EmittedException;
		});

		$this->emitter->emitEvent('Test', 'test');
	}

	public function testStaticCallback(): void {
		$this->expectException(EmittedException::class);

		$this->emitter->listen('Test', 'test', [self::class, 'staticCallBack']);

		$this->emitter->emitEvent('Test', 'test');
	}

	public function testNonStaticCallback(): void {
		$this->expectException(EmittedException::class);

		$this->emitter->listen('Test', 'test', [$this, 'nonStaticCallBack']);

		$this->emitter->emitEvent('Test', 'test');
	}

	public function testOnlyCallOnce(): void {
		$count = 0;
		$listener = function () use (&$count): void {
			$count++;
		};

		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(1, $count);
	}

	public function testDifferentMethods(): void {
		$testCount = 0;
		$fooCount = 0;

		$this->emitter->listen('Test', 'test', function () use (&$testCount): void {
			$testCount++;
		});
		$this->emitter->listen('Test', 'foo', function () use (&$fooCount): void {
			$fooCount++;
		});

		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(1, $testCount);
		$this->assertSame(0, $fooCount);

		$this->emitter->emitEvent('Test', 'foo');

		$this->assertSame(1, $testCount);
		$this->assertSame(1, $fooCount);
	}

	public function testDifferentScopes(): void {
		$testScopeCount = 0;
		$barScopeCount = 0;

		$this->emitter->listen('Test', 'test', function () use (&$testScopeCount): void {
			$testScopeCount++;
		});
		$this->emitter->listen('Bar', 'test', function () use (&$barScopeCount): void {
			$barScopeCount++;
		});

		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(1, $testScopeCount);
		$this->assertSame(0, $barScopeCount);

		$this->emitter->emitEvent('Bar', 'test');

		$this->assertSame(1, $testScopeCount);
		$this->assertSame(1, $barScopeCount);
	}

	public function testDifferentCallbacks(): void {
		$listener1Count = 0;
		$listener2Count = 0;

		$listener1 = function () use (&$listener1Count): void {
			$listener1Count++;
		};
		$listener2 = function () use (&$listener2Count): void {
			$listener2Count++;
		};

		$this->emitter->listen('Test', 'test', $listener1);
		$this->emitter->listen('Test', 'test', $listener2);
		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(1, $listener1Count);
		$this->assertSame(1, $listener2Count);
	}

	public function testArguments(): void {
		$receivedArguments = null;

		$this->emitter->listen('Test', 'test', function ($foo, $bar) use (&$receivedArguments): void {
			$receivedArguments = [$foo, $bar];
		});

		$this->emitter->emitEvent('Test', 'test', ['foo', 'bar']);

		$this->assertSame(['foo', 'bar'], $receivedArguments);
	}

	public function testNamedArguments(): void {
		$receivedArguments = null;

		$this->emitter->listen('Test', 'test', function ($bar, $foo) use (&$receivedArguments): void {
			$receivedArguments = [$foo, $bar];
		});

		$this->emitter->emitEvent('Test', 'test', [
			'foo' => 'foo',
			'bar' => 'bar',
		]);

		$this->assertSame(['foo', 'bar'], $receivedArguments);
	}

	public function testRemoveSpecifiedCallback(): void {
		$count = 0;
		$listener = function () use (&$count): void {
			$count++;
		};

		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->removeListener('Test', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(0, $count);
	}

	public function testRemoveAllListenersForEvent(): void {
		$listener1Count = 0;
		$listener2Count = 0;

		$listener1 = function () use (&$listener1Count): void {
			$listener1Count++;
		};
		$listener2 = function () use (&$listener2Count): void {
			$listener2Count++;
		};

		$this->emitter->listen('Test', 'test', $listener1);
		$this->emitter->listen('Test', 'test', $listener2);
		$this->emitter->removeListener('Test', 'test');
		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(0, $listener1Count);
		$this->assertSame(0, $listener2Count);
	}

	public function testRemoveAllMethodsForScope(): void {
		$testCount = 0;
		$fooCount = 0;
		$otherScopeCount = 0;

		$this->emitter->listen('Test', 'test', function () use (&$testCount): void {
			$testCount++;
		});
		$this->emitter->listen('Test', 'foo', function () use (&$fooCount): void {
			$fooCount++;
		});
		$this->emitter->listen('Bar', 'foo', function () use (&$otherScopeCount): void {
			$otherScopeCount++;
		});

		// Remove all methods under the Test scope.
		$this->emitter->removeListener('Test', null);

		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Test', 'foo');
		$this->emitter->emitEvent('Bar', 'foo');

		$this->assertSame(0, $testCount);
		$this->assertSame(0, $fooCount);
		$this->assertSame(1, $otherScopeCount);
	}

	public function testRemoveMethodAcrossAllScopes(): void {
		$testScopeCount = 0;
		$barScopeCount = 0;
		$otherMethodCount = 0;

		$this->emitter->listen('Test', 'test', function () use (&$testScopeCount): void {
			$testScopeCount++;
		});
		$this->emitter->listen('Bar', 'test', function () use (&$barScopeCount): void {
			$barScopeCount++;
		});
		$this->emitter->listen('Test', 'foo', function () use (&$otherMethodCount): void {
			$otherMethodCount++;
		});

		// Remove this method across all scopes.
		$this->emitter->removeListener(null, 'test');

		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Bar', 'test');
		$this->emitter->emitEvent('Test', 'foo');

		$this->assertSame(0, $testScopeCount);
		$this->assertSame(0, $barScopeCount);
		$this->assertSame(1, $otherMethodCount);
	}

	public function testRemoveCallbackEverywhere(): void {
		$removedCount = 0;
		$keptCount = 0;

		$listener = function () use (&$removedCount): void {
			$removedCount++;
		};
		$remainingListener = function () use (&$keptCount): void {
			$keptCount++;
		};

		// Register the same target callback in multiple combinations.
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Test', 'foo', $listener);
		$this->emitter->listen('Bar', 'foo', $listener);

		// Register an unrelated callback alongside each target registration.
		$this->emitter->listen('Test', 'test', $remainingListener);
		$this->emitter->listen('Test', 'foo', $remainingListener);
		$this->emitter->listen('Bar', 'foo', $remainingListener);

		// Remove the target callback from every scope and method.
		$this->emitter->removeListener(null, null, $listener);

		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Test', 'foo');
		$this->emitter->emitEvent('Bar', 'foo');

		$this->assertSame(0, $removedCount);
		$this->assertSame(3, $keptCount);
	}

	public function testRemoveKeepOtherCallback(): void {
		$remainingListenerCount = 0;

		$listenerToRemove = function (): void {
			throw new \LogicException('Removed listener was called');
		};
		$remainingListener = function () use (&$remainingListenerCount): void {
			$remainingListenerCount++;
		};

		$this->emitter->listen('Test', 'test', $listenerToRemove);
		$this->emitter->listen('Test', 'test', $remainingListener);
		$this->emitter->removeListener('Test', 'test', $listenerToRemove);
		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(1, $remainingListenerCount);
	}

	public function testRemoveKeepOtherMethod(): void {
		$testCount = 0;
		$fooCount = 0;

		$testListener = function () use (&$testCount): void {
			$testCount++;
		};
		$fooListener = function () use (&$fooCount): void {
			$fooCount++;
		};

		$this->emitter->listen('Test', 'test', $testListener);
		$this->emitter->listen('Test', 'foo', $fooListener);
		$this->emitter->removeListener('Test', 'foo', $fooListener);
		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Test', 'foo');

		$this->assertSame(1, $testCount);
		$this->assertSame(0, $fooCount);
	}

	public function testRemoveKeepOtherScope(): void {
		$testScopeCount = 0;
		$barScopeCount = 0;

		$testListener = function () use (&$testScopeCount): void {
			$testScopeCount++;
		};
		$barListener = function () use (&$barScopeCount): void {
			$barScopeCount++;
		};

		$this->emitter->listen('Test', 'test', $testListener);
		$this->emitter->listen('Bar', 'test', $barListener);
		$this->emitter->removeListener('Bar', 'test', $barListener);

		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Bar', 'test');

		$this->assertSame(1, $testScopeCount);
		$this->assertSame(0, $barScopeCount);
	}

	public function testRemoveNonExistingName(): void {
		$count = 0;
		$listener = function () use (&$count): void {
			$count++;
		};

		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->removeListener('Bar', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->assertSame(1, $count);
	}
}
