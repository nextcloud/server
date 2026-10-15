<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Interaction;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Interaction\InteractionAction;
use OCP\Interaction\InteractionReceiver;
use OCP\Interaction\InteractionResource;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\IUser;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

final class RestrictInteractionEventTest extends TestCase {
	/**
	 * @return list<array{bool}>
	 */
	public static function dataIsInteractionRestricted(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataIsInteractionRestricted')]
	public function testIsInteractionRestricted(bool $isRestricted): void {
		$eventDispatcher = Server::get(IEventDispatcher::class);

		$auditEvents = [];
		$auditEventListener = function (CriticalActionPerformedEvent $event) use (&$auditEvents): void {
			$auditEvents[] = $event;
		};
		$eventDispatcher->addListener(CriticalActionPerformedEvent::class, $auditEventListener);

		/** @psalm-suppress UnusedClosureParam */
		$restrictInteractionEventListener = function (RestrictInteractionEvent $event) use ($isRestricted): void {
			if ($isRestricted) {
				throw new InteractionRestrictedException('my restriction message', 'my restriction hint');
			}
		};
		$eventDispatcher->addListener(RestrictInteractionEvent::class, $restrictInteractionEventListener);

		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('my-uid');

		$resource1 = $this->createMock(InteractionResource::class);
		$resource1
			->method('getID')
			->willReturn('my-resource1');

		$resource2 = $this->createMock(InteractionResource::class);
		$resource2
			->method('getID')
			->willReturn('my-resource2');

		$action = $this->createStub(InteractionAction::class);

		$receiver1 = $this->createMock(InteractionReceiver::class);
		$receiver1
			->method('getID')
			->willReturn('my-receiver1');

		$receiver2 = $this->createMock(InteractionReceiver::class);
		$receiver2
			->method('getID')
			->willReturn('my-receiver2');

		$event = new RestrictInteractionEvent(
			$user->getUID(),
			$user,
			[$resource1, $resource2],
			$action,
			[$receiver1, $receiver2],
		);

		if ($isRestricted) {
			$this->assertEquals('my restriction hint', $event->isInteractionRestricted());
		} else {
			$this->assertFalse($event->isInteractionRestricted());
		}

		$this->assertEquals([
			new CriticalActionPerformedEvent(
				$isRestricted
					? 'Interaction %s from user %s on %s to %s is restricted: my restriction message'
					: 'Interaction %s from user %s on %s to %s is allowed.',
				[
					$action::class,
					'my-uid',
					'my-resource1, my-resource2',
					'my-receiver1, my-receiver2',
				],
			),
		], $auditEvents);

		$eventDispatcher->removeListener(CriticalActionPerformedEvent::class, $auditEventListener);
		$eventDispatcher->removeListener(RestrictInteractionEvent::class, $restrictInteractionEventListener);
	}
}
