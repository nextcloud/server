<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log\Audit;

use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class CriticalActionPerformedEventTest extends TestCase {
	public function testWithoutOperation(): void {
		$event = new CriticalActionPerformedEvent('Share "%s" was accepted', ['42']);

		$this->assertNull($event->getOperation());
	}

	public static function validOperationProvider(): array {
		return [
			['auth.login.failed'],
			['federatedfilesharing.share.accepted'],
			['files.cache_entry.inserted'],
			['app2.entity.action3'],
		];
	}

	#[DataProvider('validOperationProvider')]
	public function testValidOperation(string $operation): void {
		$event = new CriticalActionPerformedEvent('Share "%s" was accepted', ['42'], false, $operation);

		$this->assertSame($operation, $event->getOperation());
	}

	public static function invalidOperationProvider(): array {
		return [
			'empty' => [''],
			'free text' => ['Accepted share!!'],
			'one segment' => ['login'],
			'two segments' => ['auth.login'],
			'four segments' => ['deck.card.label.added'],
			'uppercase' => ['Auth.Login.Failed'],
			'hyphen' => ['auth.login.two-factor'],
			'leading digit' => ['1auth.login.failed'],
			'empty segment' => ['auth..failed'],
			'trailing dot' => ['auth.login.failed.'],
			'whitespace' => [' auth.login.failed'],
			'fqn' => ['OCA\\FederatedFileSharing\\Events\\ShareAccepted'],
		];
	}

	#[DataProvider('invalidOperationProvider')]
	public function testInvalidOperationThrows(string $operation): void {
		$this->expectException(\InvalidArgumentException::class);

		new CriticalActionPerformedEvent('Share "%s" was accepted', ['42'], false, $operation);
	}
}
