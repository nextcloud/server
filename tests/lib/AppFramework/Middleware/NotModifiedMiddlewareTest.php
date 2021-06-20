<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\NotModifiedMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
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

	public function dataModified(): array {
		$now = new \DateTime();

		return [
			[null, '', null, '', false],
			['etag', 'etag', null, '', false],
			['etag', '"wrongetag"', null, '', false],
			['etag', '', null, '', false],
			[null, '"etag"', null, '', false],
			['etag', '"etag"', null, '', true],

			[null, '', $now, $now->format(\DateTime::RFC2822), true],
			[null, '', $now, $now->format(\DateTime::ATOM), false],
			[null, '', null, $now->format(\DateTime::RFC2822), false],
			[null, '', $now, '', false],

			['etag', '"etag"', $now, $now->format(\DateTime::ATOM), true],
			['etag', '"etag"', $now, $now->format(\DateTime::RFC2822), true],
		];
	}

	/**
	 * @dataProvider dataModified
	 */
	public function testMiddleware(?string $etag, string $etagHeader, ?\DateTime $lastModified, string $lastModifiedHeader, bool $notModifiedSet) {
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

		$response = new Http\Response();
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
