<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\Listener;

use OC\Authentication\Token\IProvider;
use OCA\CloudFederationAPI\Listener\ShareDeletedListener;
use OCA\CloudFederationAPI\Service\OcmTokenService;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ShareDeletedListenerTest extends TestCase {
	private OcmTokenService&MockObject $tokenService;
	private IProvider&MockObject $tokenProvider;
	private ShareDeletedListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->tokenService = $this->createMock(OcmTokenService::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->listener = new ShareDeletedListener($this->tokenService, $this->tokenProvider);
	}

	private function event(int $shareType, ?string $token): ShareDeletedEvent {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getToken')->willReturn($token);
		return new ShareDeletedEvent($share);
	}

	public function testHandleFederatedShareRevokesTokens(): void {
		$this->tokenService->expects($this->once())
			->method('revokeByRefreshToken')->with('secret');
		$this->tokenProvider->expects($this->once())
			->method('invalidateToken')->with('secret');

		$this->listener->handle($this->event(IShare::TYPE_REMOTE, 'secret'));
	}

	public function testHandleIgnoresNonFederatedShare(): void {
		$this->tokenService->expects($this->never())->method('revokeByRefreshToken');
		$this->tokenProvider->expects($this->never())->method('invalidateToken');

		$this->listener->handle($this->event(IShare::TYPE_USER, 'secret'));
	}

	public function testHandleIgnoresEmptyToken(): void {
		$this->tokenService->expects($this->never())->method('revokeByRefreshToken');
		$this->tokenProvider->expects($this->never())->method('invalidateToken');

		$this->listener->handle($this->event(IShare::TYPE_REMOTE, ''));
	}
}
