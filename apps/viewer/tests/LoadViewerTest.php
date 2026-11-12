<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests;

use OCA\Viewer\Event\LoadViewer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;
use Test\TestCase;

/**
 * Nothing listens to the viewer's event any more, but apps still construct
 * it without checking that it exists: that has to keep working until 39.
 */
class LoadViewerTest extends TestCase {
	public function testAppsCanStillDispatchIt(): void {
		$dispatcher = Server::get(IEventDispatcher::class);
		$heard = [];
		$listener = static function (Event $event) use (&$heard): void {
			$heard[] = $event;
		};
		$dispatcher->addListener(LoadViewer::class, $listener);

		$event = new LoadViewer();
		$dispatcher->dispatchTyped($event);
		$dispatcher->removeListener(LoadViewer::class, $listener);

		$this->assertSame([$event], $heard);
	}
}
