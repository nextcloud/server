<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Hooks;

use OC\Hooks\BasicEmitter;

/**
 * Class DummyEmitter
 *
 * class to make BasicEmitter::emit publicly available
 *
 * @package Test\Hooks
 */
class DummyEmitter extends BasicEmitter {
	public function emitEvent($scope, $method, $arguments = []) {
		$this->emit($scope, $method, $arguments);
	}
}

/**
 * Class EmittedException
 *
 * a dummy exception so we can check if an event is emitted
 *
 * @package Test\Hooks
 */
class EmittedException extends \Exception {
}

class BasicEmitterTest extends \Test\TestCase {
	/**
	 * @var \OC\Hooks\Emitter $emitter
	 */
	protected $emitter;

	protected function setUp(): void {
		parent::setUp();
		$this->emitter = new DummyEmitter();
	}

	public function nonStaticCallBack() {
		throw new EmittedException;
	}

	public static function staticCallBack() {
		throw new EmittedException;
	}


	public function testAnonymousFunction(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$this->emitter->listen('Test', 'test', function (): void {
			throw new EmittedException;
		});
		$this->emitter->emitEvent('Test', 'test');
	}


	public function testStaticCallback(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$this->emitter->listen('Test', 'test', ['\Test\Hooks\BasicEmitterTest', 'staticCallBack']);
		$this->emitter->emitEvent('Test', 'test');
	}


	public function testNonStaticCallback(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

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
		$this->assertEquals(1, $count, 'Listener called an invalid number of times (' . $count . ') expected 1');
	}

	public function testDifferentMethods(): void {
		$count = 0;
		$listener = function () use (&$count): void {
			$count++;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Test', 'foo', $listener);
		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Test', 'foo');
		$this->assertEquals(2, $count, 'Listener called an invalid number of times (' . $count . ') expected 2');
	}

	public function testDifferentScopes(): void {
		$count = 0;
		$listener = function () use (&$count): void {
			$count++;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Bar', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Bar', 'test');
		$this->assertEquals(2, $count, 'Listener called an invalid number of times (' . $count . ') expected 2');
	}

	public function testDifferentCallbacks(): void {
		$count = 0;
		$listener1 = function () use (&$count): void {
			$count++;
		};
		$listener2 = function () use (&$count): void {
			$count++;
		};
		$this->emitter->listen('Test', 'test', $listener1);
		$this->emitter->listen('Test', 'test', $listener2);
		$this->emitter->emitEvent('Test', 'test');
		$this->assertEquals(2, $count, 'Listener called an invalid number of times (' . $count . ') expected 2');
	}


	public function testArguments(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$this->emitter->listen('Test', 'test', function ($foo, $bar): void {
			if ($foo == 'foo' and $bar == 'bar') {
				throw new EmittedException;
			}
		});
		$this->emitter->emitEvent('Test', 'test', ['foo', 'bar']);
	}


	public function testNamedArguments(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$this->emitter->listen('Test', 'test', function ($foo, $bar): void {
			if ($foo == 'foo' and $bar == 'bar') {
				throw new EmittedException;
			}
		});
		$this->emitter->emitEvent('Test', 'test', ['foo' => 'foo', 'bar' => 'bar']);
	}

	public function testRemoveAllSpecified(): void {
		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->removeListener('Test', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->addToAssertionCount(1);
	}

	public function testRemoveWildcardListener(): void {
		$listener1 = function (): void {
			throw new EmittedException;
		};
		$listener2 = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener1);
		$this->emitter->listen('Test', 'test', $listener2);
		$this->emitter->removeListener('Test', 'test');
		$this->emitter->emitEvent('Test', 'test');

		$this->addToAssertionCount(1);
	}

	public function testRemoveWildcardMethod(): void {
		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Test', 'foo', $listener);
		$this->emitter->removeListener('Test', null, $listener);
		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Test', 'foo');

		$this->addToAssertionCount(1);
	}

	public function testRemoveWildcardScope(): void {
		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Bar', 'test', $listener);
		$this->emitter->removeListener(null, 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Bar', 'test');

		$this->addToAssertionCount(1);
	}

	public function testRemoveWildcardScopeAndMethod(): void {
		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Test', 'foo', $listener);
		$this->emitter->listen('Bar', 'foo', $listener);
		$this->emitter->removeListener(null, null, $listener);
		$this->emitter->emitEvent('Test', 'test');
		$this->emitter->emitEvent('Test', 'foo');
		$this->emitter->emitEvent('Bar', 'foo');

		$this->addToAssertionCount(1);
	}


	public function testRemoveKeepOtherCallback(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$listener1 = function (): void {
			throw new EmittedException;
		};
		$listener2 = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener1);
		$this->emitter->listen('Test', 'test', $listener2);
		$this->emitter->removeListener('Test', 'test', $listener1);
		$this->emitter->emitEvent('Test', 'test');

		$this->addToAssertionCount(1);
	}


	public function testRemoveKeepOtherMethod(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Test', 'foo', $listener);
		$this->emitter->removeListener('Test', 'foo', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->addToAssertionCount(1);
	}


	public function testRemoveKeepOtherScope(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->listen('Bar', 'test', $listener);
		$this->emitter->removeListener('Bar', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->addToAssertionCount(1);
	}


	public function testRemoveNonExistingName(): void {
		$this->expectException(\Test\Hooks\EmittedException::class);

		$listener = function (): void {
			throw new EmittedException;
		};
		$this->emitter->listen('Test', 'test', $listener);
		$this->emitter->removeListener('Bar', 'test', $listener);
		$this->emitter->emitEvent('Test', 'test');

		$this->addToAssertionCount(1);
	}
}
