<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests;

use OCA\AdminAudit\Operation;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class OperationTest extends TestCase {
	public static function operationProvider(): array {
		return array_map(static fn (Operation $operation): array => [$operation], Operation::cases());
	}

	/**
	 * The app's own operations follow the format enforced for operations from other apps.
	 */
	#[DataProvider('operationProvider')]
	public function testOperationFormat(Operation $operation): void {
		$event = new CriticalActionPerformedEvent('', [], false, $operation->value);

		$this->assertSame($operation->value, $event->getOperation());
	}
}
