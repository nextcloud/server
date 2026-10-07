<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\TagEventListener;
use OCP\SystemTag\Events\TagCreatedEvent;
use OCP\SystemTag\ISystemTag;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TagEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private TagEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new TagEventListener($this->logger);
	}

	public function testTagCreated(): void {
		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getName')->willReturn('confidential');
		$tag->method('isUserVisible')->willReturn(false);
		$tag->method('isUserAssignable')->willReturn(false);

		$this->logger->expects($this->once())
			->method('info')
			->with('System tag "confidential" (invisible, system only) created', ['app' => 'admin_audit', 'operation' => 'systemtags.tag.created', 'params' => ['name' => 'confidential', 'visibility' => 'invisible', 'assignable' => 'system only']]);

		$this->listener->handle(new TagCreatedEvent($tag));
	}
}
