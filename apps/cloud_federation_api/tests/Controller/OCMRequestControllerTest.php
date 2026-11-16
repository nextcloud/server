<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\Controller;

use OCA\CloudFederationAPI\Controller\OCMRequestController;
use OCP\AppFramework\Http\Response;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\OCM\Events\OCMEndpointRequestEvent;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\IIncomingSignedRequest;
use Psr\Log\LoggerInterface;
use Test\TestCase;

final class OCMRequestControllerTest extends TestCase {
	public function testEndpointEventCarriesVerifiedRequest(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn([]);
		$request->method('getMethod')->willReturn('GET');

		$signedRequest = $this->createMock(IIncomingSignedRequest::class);
		$signedRequest->method('getBody')->willReturn('');
		$signedRequest->method('getOrigin')->willReturn('sender.example.org');

		$discovery = $this->createMock(IOCMDiscoveryService::class);
		$discovery->expects($this->once())
			->method('getIncomingSignedRequest')
			->with(null)
			->willReturn($signedRequest);

		$expectedResponse = new Response(200);
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(
				static function (OCMEndpointRequestEvent $event) use ($expectedResponse, $signedRequest): OCMEndpointRequestEvent {
					self::assertSame($signedRequest, $event->getSignedRequest());
					self::assertSame('sender.example.org', $event->getRemote());
					$event->setResponse($expectedResponse);
					return $event;
				},
			);

		$controller = new OCMRequestController(
			'cloud_federation_api',
			$request,
			$dispatcher,
			$discovery,
			$this->createMock(LoggerInterface::class),
		);

		$this->assertSame(
			$expectedResponse,
			$controller->manageOCMRequests('mls-key-packages'),
		);
	}
}
