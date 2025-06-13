<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Utility\ITimeFactory;

class ResponseTest extends \Test\TestCase {
	/**
	 * @var \OCP\AppFramework\Http\Response
	 */
	private $childResponse;

	protected function setUp(): void {
		parent::setUp();
		$this->childResponse = new Response();
	}


	public function testAddHeader(): void {
		$this->childResponse->addHeader(' hello ', 'world');
		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('world', $headers['hello']);
	}


	public function testSetHeaders(): void {
		$expected = [
			'Last-Modified' => 1,
			'ETag' => 3,
			'Something-Else' => 'hi',
			'X-Robots-Tag' => 'noindex, nofollow',
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
		];

		$this->childResponse->setHeaders($expected);
		$expected['Content-Security-Policy'] = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";
		$expected['Feature-Policy'] = "autoplay 'none';camera 'none';fullscreen 'none';geolocation 'none';microphone 'none';payment 'none'";

		$headers = $this->childResponse->getHeaders();
		unset($headers['X-Request-Id']);

		$this->assertEquals($expected, $headers);
	}

	public function testOverwriteCsp(): void {
		$expected = [
			'Content-Security-Policy' => "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' 'unsafe-inline';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self' data:;connect-src 'self';media-src 'self'",
		];
		$policy = new ContentSecurityPolicy();

		$this->childResponse->setContentSecurityPolicy($policy);
		$headers = $this->childResponse->getHeaders();

		$this->assertEquals(array_merge($expected, $headers), $headers);
	}

	public function testGetCsp(): void {
		$policy = new ContentSecurityPolicy();

		$this->childResponse->setContentSecurityPolicy($policy);
		$this->assertEquals($policy, $this->childResponse->getContentSecurityPolicy());
	}

	public function testGetCspEmpty(): void {
		$this->assertEquals(new EmptyContentSecurityPolicy(), $this->childResponse->getContentSecurityPolicy());
	}

	public function testAddHeaderValueNullDeletesIt(): void {
		$this->childResponse->addHeader('hello', 'world');
		$this->childResponse->addHeader('hello', null);
		$this->assertEquals(5, count($this->childResponse->getHeaders()));
	}


	public function testCacheHeadersAreDisabledByDefault(): void {
		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('no-cache, no-store, must-revalidate', $headers['Cache-Control']);
	}


	public function testAddCookie(): void {
		$this->childResponse->addCookie('foo', 'bar');
		$this->childResponse->addCookie('bar', 'foo', new \DateTime('1970-01-01'));

		$expectedResponse = [
			'foo' => [
				'value' => 'bar',
				'expireDate' => null,
				'sameSite' => 'Lax',
			],
			'bar' => [
				'value' => 'foo',
				'expireDate' => new \DateTime('1970-01-01'),
				'sameSite' => 'Lax',
			]
		];
		$this->assertEquals($expectedResponse, $this->childResponse->getCookies());
	}


	public function testSetCookies(): void {
		$expected = [
			'foo' => [
				'value' => 'bar',
				'expireDate' => null,
			],
			'bar' => [
				'value' => 'foo',
				'expireDate' => new \DateTime('1970-01-01')
			]
		];

		$this->childResponse->setCookies($expected);
		$cookies = $this->childResponse->getCookies();

		$this->assertEquals($expected, $cookies);
	}


	public function testInvalidateCookie(): void {
		$this->childResponse->addCookie('foo', 'bar');
		$this->childResponse->invalidateCookie('foo');
		$expected = [
			'foo' => [
				'value' => 'expired',
				'expireDate' => new \DateTime('1971-01-01'),
				'sameSite' => 'Lax',
			]
		];

		$cookies = $this->childResponse->getCookies();

		$this->assertEquals($expected, $cookies);
	}


	public function testInvalidateCookies(): void {
		$this->childResponse->addCookie('foo', 'bar');
		$this->childResponse->addCookie('bar', 'foo');
		$expected = [
			'foo' => [
				'value' => 'bar',
				'expireDate' => null,
				'sameSite' => 'Lax',
			],
			'bar' => [
				'value' => 'foo',
				'expireDate' => null,
				'sameSite' => 'Lax',
			]
		];
		$cookies = $this->childResponse->getCookies();
		$this->assertEquals($expected, $cookies);

		$this->childResponse->invalidateCookies(['foo', 'bar']);
		$expected = [
			'foo' => [
				'value' => 'expired',
				'expireDate' => new \DateTime('1971-01-01'),
				'sameSite' => 'Lax',
			],
			'bar' => [
				'value' => 'expired',
				'expireDate' => new \DateTime('1971-01-01'),
				'sameSite' => 'Lax',
			]
		];

		$cookies = $this->childResponse->getCookies();
		$this->assertEquals($expected, $cookies);
	}


	public function testRenderReturnNullByDefault(): void {
		$this->assertEquals(null, $this->childResponse->render());
	}


	public function testGetStatus(): void {
		$default = $this->childResponse->getStatus();

		$this->childResponse->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_OK, $default);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->childResponse->getStatus());
	}


	public function testGetEtag(): void {
		$this->childResponse->setEtag('hi');
		$this->assertSame('hi', $this->childResponse->getEtag());
	}


	public function testGetLastModified(): void {
		$lastModified = new \DateTime('now', new \DateTimeZone('GMT'));
		$lastModified->setTimestamp(1);
		$this->childResponse->setLastModified($lastModified);
		$this->assertEquals($lastModified, $this->childResponse->getLastModified());
	}



	public function testCacheSecondsZero(): void {
		$this->childResponse->cacheFor(0);

		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('no-cache, no-store, must-revalidate', $headers['Cache-Control']);
		$this->assertFalse(isset($headers['Expires']));
	}


	public function testCacheSeconds(): void {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getTime')
			->willReturn(1234567);

		$this->overwriteService(ITimeFactory::class, $time);

		$this->childResponse->cacheFor(33);

		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('private, max-age=33, must-revalidate', $headers['Cache-Control']);
		$this->assertEquals('Thu, 15 Jan 1970 06:56:40 GMT', $headers['Expires']);
	}



	public function testEtagLastModifiedHeaders(): void {
		$lastModified = new \DateTime('now', new \DateTimeZone('GMT'));
		$lastModified->setTimestamp(1);
		$this->childResponse->setLastModified($lastModified);
		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('Thu, 01 Jan 1970 00:00:01 GMT', $headers['Last-Modified']);
	}

	public function testChainability(): void {
		$lastModified = new \DateTime('now', new \DateTimeZone('GMT'));
		$lastModified->setTimestamp(1);

		$this->childResponse->setEtag('hi')
			->setStatus(Http::STATUS_NOT_FOUND)
			->setLastModified($lastModified)
			->cacheFor(33)
			->addHeader('hello', 'world');

		$headers = $this->childResponse->getHeaders();

		$this->assertEquals('world', $headers['hello']);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->childResponse->getStatus());
		$this->assertEquals('hi', $this->childResponse->getEtag());
		$this->assertEquals('Thu, 01 Jan 1970 00:00:01 GMT', $headers['Last-Modified']);
		$this->assertEquals('private, max-age=33, must-revalidate',
			$headers['Cache-Control']);
	}

	public function testThrottle(): void {
		$this->assertFalse($this->childResponse->isThrottled());
		$this->childResponse->throttle();
		$this->assertTrue($this->childResponse->isThrottled());
	}

	public function testGetThrottleMetadata(): void {
		$this->childResponse->throttle(['foo' => 'bar']);
		$this->assertSame(['foo' => 'bar'], $this->childResponse->getThrottleMetadata());
	}
}
