<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\NotModifiedMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class NotModifiedMiddlewareTest extends \Test\TestCase {
	/** @var IRequest */
	private $request;
	/** @var Controller */
	private $controller;
	/** @var NotModifiedMiddleware */
	private $middleWare;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->middleWare = new NotModifiedMiddleware(
			$this->request
		);

		$this->controller = $this->createMock(Controller::class);
	}

	public static function dataModified(): array {
		$now = new \DateTime();

		return [
			[null, '', null, '', false],
			['etag', 'etag', null, '', false],
			['etag', '"wrongetag"', null, '', false],
			['etag', '', null, '', false],
			[null, '"etag"', null, '', false],
			['etag', '"etag"', null, '', true],

			[null, '', $now, $now->format(\DateTimeInterface::RFC7231), true],
			[null, '', $now, $now->format(\DateTimeInterface::ATOM), false],
			[null, '', null, $now->format(\DateTimeInterface::RFC7231), false],
			[null, '', $now, '', false],

			['etag', '"etag"', $now, $now->format(\DateTimeInterface::ATOM), true],
			['etag', '"etag"', $now, $now->format(\DateTimeInterface::RFC7231), true],
		];
	}

	/**
	 * @dataProvider dataModified
	 */
	public function testMiddleware(?string $etag, string $etagHeader, ?\DateTime $lastModified, string $lastModifiedHeader, bool $notModifiedSet): void {
		$this->request->method('getHeader')
			->willReturnCallback(function (string $name) use ($etagHeader, $lastModifiedHeader) {
				if ($name === 'IF_NONE_MATCH') {
					return $etagHeader;
				}
				if ($name === 'IF_MODIFIED_SINCE') {
					return $lastModifiedHeader;
				}
				return '';
			});

		$response = new Response();
		if ($etag !== null) {
			$response->setETag($etag);
		}
		if ($lastModified !== null) {
			$response->setLastModified($lastModified);
		}
		$response->setStatus(Http::STATUS_OK);

		$result = $this->middleWare->afterController($this->controller, 'myfunction', $response);

		if ($notModifiedSet) {
			$this->assertSame(Http::STATUS_NOT_MODIFIED, $result->getStatus());
		} else {
			$this->assertSame(Http::STATUS_OK, $result->getStatus());
		}
	}
}
