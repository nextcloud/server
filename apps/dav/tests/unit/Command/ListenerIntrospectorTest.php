<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\Command\ListenerIntrospector;
use Sabre\DAV\Server;
use Test\TestCase;

/**
 * @package OCA\DAV\Tests\unit\Command
 */
class ListenerIntrospectorTest extends TestCase {
	private ListenerIntrospector $introspector;

	protected function setUp(): void {
		parent::setUp();

		$this->introspector = new ListenerIntrospector();
	}

	public function testCollectListenersReturnsWellFormedRows(): void {
		$server = new Server();
		// A fresh Sabre server registers its own core listeners; assert we get a
		// well-formed list rather than a specific count.
		foreach ($this->introspector->collectListeners($server) as $row) {
			$this->assertArrayHasKey('event', $row);
			$this->assertArrayHasKey('priority', $row);
			$this->assertArrayHasKey('listener', $row);
			$this->assertIsInt($row['priority']);
		}
		$this->addToAssertionCount(1);
	}

	public function testCollectListenersSortsByEventThenPriority(): void {
		$server = new Server();
		$this->stripListeners($server);

		$server->on('beforeMethod:*', static function (): void {
		}, 200);
		$server->on('afterMethod:GET', [$this, 'sampleHandler'], 50);
		$server->on('afterMethod:GET', static function (): void {
		}, 10);

		$rows = $this->introspector->collectListeners($server);

		$this->assertCount(3, $rows);

		// afterMethod:GET sorts before beforeMethod:*, and within the same event
		// priority 10 sorts before priority 50.
		$this->assertSame('afterMethod:GET', $rows[0]['event']);
		$this->assertSame(10, $rows[0]['priority']);

		$this->assertSame('afterMethod:GET', $rows[1]['event']);
		$this->assertSame(50, $rows[1]['priority']);
		$this->assertSame(self::class . '::sampleHandler', $rows[1]['listener']);

		$this->assertSame('beforeMethod:*', $rows[2]['event']);
		$this->assertSame(200, $rows[2]['priority']);
	}

	public function testCollectListenersDescribesCallableTypes(): void {
		$server = new Server();
		$this->stripListeners($server);

		$server->on('event:closure', static function (): void {
		});
		$server->on('event:method', [$this, 'sampleHandler']);
		$server->on('event:static', [self::class, 'staticHandler']);
		$server->on('event:invokable', new SampleInvokableHandler());
		$server->on('event:fcc', $this->sampleHandler(...));

		$byEvent = [];
		foreach ($this->introspector->collectListeners($server) as $row) {
			$byEvent[$row['event']] = $row['listener'];
		}

		// PHP 8.4+ renders "{closure:Class::method()} (file:line)", older PHP
		// renders "{closure:Class} (file:line)" — both name the defining class
		// and include the file location.
		$this->assertStringContainsString(self::class, $byEvent['event:closure']);
		$this->assertStringContainsString('closure', $byEvent['event:closure']);
		$this->assertStringContainsString('ListenerIntrospectorTest.php:', $byEvent['event:closure']);
		$this->assertSame(self::class . '::sampleHandler', $byEvent['event:method']);
		$this->assertSame(self::class . '::staticHandler', $byEvent['event:static']);
		$this->assertSame(SampleInvokableHandler::class . '::__invoke', $byEvent['event:invokable']);
		// A first-class callable resolves to the method it wraps, marked as a
		// closure so it is distinguishable from a plain method callable.
		$this->assertSame('{closure:' . self::class . '::sampleHandler}', $byEvent['event:fcc']);
	}

	public function testCollectListenersMarksWildcardEvents(): void {
		$server = new Server();
		$this->stripListeners($server);

		$server->on('beforeMethod:*', static function (): void {
		});

		$rows = $this->introspector->collectListeners($server);

		$this->assertCount(1, $rows);
		$this->assertSame('beforeMethod:*', $rows[0]['event']);
	}

	public function testCollectListenersUnwrapsQueryMonitoredListeners(): void {
		$server = new MonitoringTestServer();
		$this->stripListeners($server);

		// Mimic OCA\DAV\Connector\Sabre\Server::monitorPropfindQueries(), which
		// registers a wrapper closure and records the original in a side map at
		// the same index.
		$original = [$this, 'sampleHandler'];
		$wrapped = static function (): void {
		};
		$server->originalListeners['propFind'][] = $original;
		$server->wrappedListeners['propFind'][] = $wrapped;
		$server->on('propFind', $wrapped, 100);

		$rows = $this->introspector->collectListeners($server);

		$this->assertCount(1, $rows);
		$this->assertSame('propFind', $rows[0]['event']);
		$this->assertSame(self::class . '::sampleHandler (query-monitored)', $rows[0]['listener']);
	}

	public function testFindDeferredHandlersReturnsOnlyClosuresFromSourceFile(): void {
		$server = new Server();
		$this->stripListeners($server);

		$deferred = static function (): void {
		};
		$server->on('beforeMethod:*', $deferred, 100);
		// A non-closure listener and a closure from a different file must be
		// ignored.
		$server->on('beforeMethod:*', [$this, 'sampleHandler'], 100);

		$handlers = $this->introspector->findDeferredHandlers($server, __FILE__);
		$this->assertCount(1, $handlers);
		$this->assertSame($deferred, $handlers[0]);

		$this->assertSame([], $this->introspector->findDeferredHandlers($server, '/some/other/file.php'));
	}

	public function testResolveFiringOrderMergesWildcardAndExactByPriority(): void {
		$server = new Server();
		$this->stripListeners($server);

		$closure = static function (): void {
		};
		$server->on('beforeMethod:GET', [$this, 'sampleHandler'], 100); // exact, prio 100
		$server->on('beforeMethod:*', $closure, 10);                    // wildcard, prio 10
		$server->on('beforeMethod:*', [self::class, 'staticHandler'], 100); // wildcard, prio 100
		// An unrelated wildcard family must not leak into beforeMethod:GET.
		$server->on('afterMethod:*', static function (): void {
		}, 5);

		$chain = $this->introspector->resolveFiringOrder($server, 'beforeMethod:GET');

		// Wildcard and exact listeners are merged; the unrelated afterMethod:*
		// listener does not leak in.
		$this->assertCount(3, $chain);

		// Sorted by priority ascending, numbered 1..n.
		$this->assertSame([10, 100, 100], array_column($chain, 'priority'));
		$this->assertSame([1, 2, 3], array_column($chain, 'order'));

		// The lowest-priority (wildcard) listener fires first.
		$this->assertStringContainsString('closure', $chain[0]['listener']);
		$this->assertSame('beforeMethod:*', $chain[0]['registeredOn']);

		// Both priority-100 listeners are present. Their relative order for equal
		// priority is decided by Sabre's array_multisort tie-break, not by us, so
		// we do not assert it here (see the listeners() equality test below).
		$listeners = array_column($chain, 'listener');
		$this->assertContains(self::class . '::sampleHandler', $listeners);
		$this->assertContains(self::class . '::staticHandler', $listeners);

		// Each row is attributed to the event name it was registered on.
		$registeredOnByListener = array_column($chain, 'registeredOn', 'listener');
		$this->assertSame('beforeMethod:GET', $registeredOnByListener[self::class . '::sampleHandler']);
		$this->assertSame('beforeMethod:*', $registeredOnByListener[self::class . '::staticHandler']);
	}

	public function testResolveFiringSequenceMatchesSabresOwnListenerOrder(): void {
		$server = new Server();
		$this->stripListeners($server);

		// A representative mix: wildcard + exact, varied and duplicate priorities,
		// and different callable kinds.
		$server->on('beforeMethod:GET', [$this, 'sampleHandler'], 100);
		$server->on('beforeMethod:*', static function (): void {
		}, 10);
		$server->on('beforeMethod:*', [self::class, 'staticHandler'], 100);
		$server->on('beforeMethod:GET', new SampleInvokableHandler(), 100);
		$server->on('beforeMethod:*', $this->sampleHandler(...), 5);

		$sequence = $this->introspector->resolveFiringSequence($server, 'beforeMethod:GET');
		$callables = array_map(static fn (array $entry): callable => $entry[1], $sequence);

		// Sabre\DAV\Server::emit() iterates exactly $server->listeners($event), so
		// matching that list callable-for-callable proves the printed order is the
		// real execution order.
		$this->assertSame($server->listeners('beforeMethod:GET'), $callables);
	}

	public function testResolvedOrderMatchesRealDispatchEndToEnd(): void {
		$server = new Server();
		$this->stripListeners($server);

		$log = [];
		$listen = static function (string $marker) use (&$log): callable {
			return static function () use (&$log, $marker): void {
				$log[] = $marker;
			};
		};

		// Interleave wildcard and exact registrations across all three method
		// families, with priorities chosen so that "wildcards first, then exact"
		// (or any other separate execution) would produce a different order.
		$server->on('beforeMethod:GET', $listen('before:exact@10'), 10);
		$server->on('beforeMethod:*', $listen('before:wildcard@50'), 50);
		$server->on('beforeMethod:GET', $listen('before:exact@100'), 100);
		$server->on('beforeMethod:*', $listen('before:wildcard@150'), 150);

		$server->on('method:*', $listen('method:wildcard@90'), 90);
		$server->on('method:GET', static function ($request, $response) use (&$log): bool {
			$log[] = 'method:exact@100(serves)';
			$response->setStatus(200);
			return false; // handled — stops the (merged) chain
		}, 100);
		// If wildcard listeners ran as a separate list, this one would still be
		// invoked; in the merged chain the serving handler above stops it.
		$server->on('method:*', $listen('method:wildcard@300'), 300);

		$server->on('afterMethod:*', $listen('after:wildcard@60'), 60);
		$server->on('afterMethod:GET', $listen('after:exact@110'), 110);

		// Real dispatch through Sabre's own request pipeline.
		$server->invokeMethod(new \Sabre\HTTP\Request('GET', '/'), new \Sabre\HTTP\Response(), false);

		$this->assertSame([
			'before:exact@10',
			'before:wildcard@50',
			'before:exact@100',
			'before:wildcard@150',
			'method:wildcard@90',
			'method:exact@100(serves)',
			// no 'method:wildcard@300' — the merged chain stopped
			'after:wildcard@60',
			'after:exact@110',
		], $log);

		// The introspector displays the same merged order the dispatch used,
		// including the listener that did not run because the chain stopped.
		$this->assertSame(
			[10, 50, 100, 150],
			array_column($this->introspector->resolveFiringOrder($server, 'beforeMethod:GET'), 'priority'),
		);
		$this->assertSame(
			[90, 100, 300],
			array_column($this->introspector->resolveFiringOrder($server, 'method:GET'), 'priority'),
		);
	}

	public function sampleHandler(): void {
	}

	public static function staticHandler(): void {
	}

	/**
	 * Removes the listeners Sabre registers by default so tests can assert on an
	 * exact set of registrations.
	 */
	private function stripListeners(Server $server): void {
		$server->removeAllListeners();
	}
}

class SampleInvokableHandler {
	public function __invoke(): void {
	}
}

/**
 * Mimics the side maps that OCA\DAV\Connector\Sabre\Server keeps for its
 * query-monitoring wrappers, without pulling in the full DAV server.
 */
class MonitoringTestServer extends Server {
	/** @var array<string, list<callable>> */
	public array $originalListeners = [];
	/** @var array<string, list<callable>> */
	public array $wrappedListeners = [];
}
