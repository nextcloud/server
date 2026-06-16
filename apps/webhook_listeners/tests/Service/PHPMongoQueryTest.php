<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Tests\Service;

use OCA\WebhookListeners\Service\PHPMongoQuery;
use OCP\Files\Events\Node\NodeWrittenEvent;
use Test\TestCase;

class PHPMongoQueryTest extends TestCase {
	public static function dataExecuteQuery(): array {
		$event = [
			'event' => [
				'class' => NodeWrittenEvent::class,
				'node' => [
					'id' => 23,
					'path' => '/tmp/file.txt',
				],
			],
			'user' => [
				'uid' => 'bob',
			],
		];
		return [
			[[], [], true],
			[[], $event, true],
			[['event.class' => NodeWrittenEvent::class], $event, true],
			[['event.class' => NodeWrittenEvent::class, 'user.uid' => 'bob'], $event, true],
			[['event.node.path' => '/.txt$/'], $event, true],
			[['event.node.id' => ['$gte' => 22]], $event, true],
			[['event.class' => 'SomethingElse'], $event, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteQuery')]
	public function testExecuteQuery(array $query, array $document, bool $matches): void {
		$this->assertEquals($matches, PHPMongoQuery::executeQuery($query, $document));
	}
}
