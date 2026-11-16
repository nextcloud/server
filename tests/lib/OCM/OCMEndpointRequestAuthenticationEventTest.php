<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use OCP\OCM\Events\OCMEndpointRequestAuthenticationEvent;
use OCP\OCM\Exceptions\OCMArgumentException;
use Test\TestCase;

final class OCMEndpointRequestAuthenticationEventTest extends TestCase {
	public function testIdenticalIdentityClaimsAreDeduplicated(): void {
		$event = new OCMEndpointRequestAuthenticationEvent(
			'GET',
			'mls-key-packages',
			['senderDomain' => 'sender.example.org'],
			'signature@sender.example.org',
		);

		$event->addOcmAddress('signature@sender.example.org');

		$this->assertSame(
			'signature@sender.example.org',
			$event->getOcmAddress(),
		);
	}

	public function testListenerCannotReplaceSelectedIdentity(): void {
		$event = new OCMEndpointRequestAuthenticationEvent(
			'GET',
			'shares',
			[],
			'owner@sender.example.org',
		);
		$event->addOcmAddress('other@attacker.example.org');

		$this->expectException(OCMArgumentException::class);
		$event->getOcmAddress();
	}

	public function testEmptyIdentityIsRejected(): void {
		$event = new OCMEndpointRequestAuthenticationEvent(
			'GET',
			'shares',
			[],
			null,
		);

		$this->expectException(\InvalidArgumentException::class);
		$event->addOcmAddress('');
	}
}
