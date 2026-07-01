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
				throw new InteractionRestrictedException('my restriction');
			}
		};
		$eventDispatcher->addListener(RestrictInteractionEvent::class, $restrictInteractionEventListener);

		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn('my-uid');

		$resource = $this->createMock(InteractionResource::class);
		$resource
			->method('getID')
			->willReturn('my-resource');

		$action = $this->createStub(InteractionAction::class);

		$receiver = $this->createMock(InteractionReceiver::class);
		$receiver
			->method('getID')
			->willReturn('my-receiver');

		$event = new RestrictInteractionEvent(
			$user->getUID(),
			$user,
			$resource,
			$action,
			$receiver,
		);

		$this->assertEquals($isRestricted, $event->isInteractionRestricted());

		$this->assertEquals([
			new CriticalActionPerformedEvent(
				$isRestricted
					? 'Interaction "%s" from user "%s" on "%s" to "%s" is restricted: my restriction'
					: 'Interaction "%s" from user "%s" on "%s" to "%s" is allowed.',
				[
					$action::class,
					'my-uid',
					'my-resource',
					'my-receiver',
				],
			),
		], $auditEvents);

		$eventDispatcher->removeListener(CriticalActionPerformedEvent::class, $auditEventListener);
		$eventDispatcher->removeListener(RestrictInteractionEvent::class, $restrictInteractionEventListener);
	}
}
