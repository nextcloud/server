<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace Test\AppFramework\Http;


use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http;


class ResponseTest extends \Test\TestCase {

	/**
	 * @var \OCP\AppFramework\Http\Response
	 */
	private $childResponse;

	protected function setUp(){
		parent::setUp();
		$this->childResponse = new Response();
	}


	public function testAddHeader(){
		$this->childResponse->addHeader(' hello ', 'world');
		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('world', $headers['hello']);
	}


	public function testSetHeaders() {
		$expected = array(
			'Last-Modified' => 1,
			'ETag' => 3,
			'Something-Else' => 'hi'
		);

		$this->childResponse->setHeaders($expected);
		$headers = $this->childResponse->getHeaders();
		$expected['Content-Security-Policy'] = "default-src 'none';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self';connect-src 'self';media-src 'self'";

		$this->assertEquals($expected, $headers);
	}

	public function testOverwriteCsp() {
		$expected = [
			'Content-Security-Policy' => "default-src 'none';script-src 'self' 'unsafe-inline' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self';font-src 'self';connect-src 'self';media-src 'self'",
		];
		$policy = new Http\ContentSecurityPolicy();
		$policy->allowInlineScript(true);

		$this->childResponse->setContentSecurityPolicy($policy);
		$headers = $this->childResponse->getHeaders();

		$this->assertEquals(array_merge($expected, $headers), $headers);
	}

	public function testGetCsp() {
		$policy = new Http\ContentSecurityPolicy();
		$policy->allowInlineScript(true);

		$this->childResponse->setContentSecurityPolicy($policy);
		$this->assertEquals($policy, $this->childResponse->getContentSecurityPolicy());
	}

	public function testGetCspEmpty() {
		$this->assertNull($this->childResponse->getContentSecurityPolicy());
	}

	public function testAddHeaderValueNullDeletesIt(){
		$this->childResponse->addHeader('hello', 'world');
		$this->childResponse->addHeader('hello', null);
		$this->assertEquals(2, count($this->childResponse->getHeaders()));
	}


	public function testCacheHeadersAreDisabledByDefault(){
		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('no-cache, must-revalidate', $headers['Cache-Control']);
	}


	public function testAddCookie() {
		$this->childResponse->addCookie('foo', 'bar');
		$this->childResponse->addCookie('bar', 'foo', new \DateTime('1970-01-01'));

		$expectedResponse = array(
			'foo' => array(
				'value' => 'bar',
				'expireDate' => null,
			),
			'bar' => array(
				'value' => 'foo',
				'expireDate' => new \DateTime('1970-01-01')
			)
		);
		$this->assertEquals($expectedResponse, $this->childResponse->getCookies());
	}


	function testSetCookies() {
		$expected = array(
			'foo' => array(
				'value' => 'bar',
				'expireDate' => null,
			),
			'bar' => array(
				'value' => 'foo',
				'expireDate' => new \DateTime('1970-01-01')
			)
		);

		$this->childResponse->setCookies($expected);
		$cookies = $this->childResponse->getCookies();

		$this->assertEquals($expected, $cookies);
	}


	function testInvalidateCookie() {
		$this->childResponse->addCookie('foo', 'bar');
		$this->childResponse->invalidateCookie('foo');
		$expected = array(
			'foo' => array(
				'value' => 'expired',
				'expireDate' => new \DateTime('1971-01-01')
			)
		);

		$cookies = $this->childResponse->getCookies();

		$this->assertEquals($expected, $cookies);
	}


	function testInvalidateCookies() {
		$this->childResponse->addCookie('foo', 'bar');
		$this->childResponse->addCookie('bar', 'foo');
		$expected = array(
			'foo' => array(
				'value' => 'bar',
				'expireDate' => null
			),
			'bar' => array(
				'value' => 'foo',
				'expireDate' => null
			)
		);
		$cookies = $this->childResponse->getCookies();
		$this->assertEquals($expected, $cookies);

		$this->childResponse->invalidateCookies(array('foo', 'bar'));
		$expected = array(
			'foo' => array(
				'value' => 'expired',
				'expireDate' => new \DateTime('1971-01-01')
			),
			'bar' => array(
				'value' => 'expired',
				'expireDate' => new \DateTime('1971-01-01')
			)
		);

		$cookies = $this->childResponse->getCookies();
		$this->assertEquals($expected, $cookies);
	}


	public function testRenderReturnNullByDefault(){
		$this->assertEquals(null, $this->childResponse->render());
	}


	public function testGetStatus() {
		$default = $this->childResponse->getStatus();

		$this->childResponse->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_OK, $default);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->childResponse->getStatus());
	}


	public function testGetEtag() {
		$this->childResponse->setEtag('hi');
		$this->assertSame('hi', $this->childResponse->getEtag());
	}


	public function testGetLastModified() {
		$lastModified = new \DateTime(null, new \DateTimeZone('GMT'));
		$lastModified->setTimestamp(1);
		$this->childResponse->setLastModified($lastModified);
		$this->assertEquals($lastModified, $this->childResponse->getLastModified());
	}



	public function testCacheSecondsZero() {
		$this->childResponse->cacheFor(0);

		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('no-cache, no-store, must-revalidate', $headers['Cache-Control']);
	}


	public function testCacheSeconds() {
		$this->childResponse->cacheFor(33);

		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('max-age=33, must-revalidate',
			$headers['Cache-Control']);
	}



	public function testEtagLastModifiedHeaders() {
		$lastModified = new \DateTime(null, new \DateTimeZone('GMT'));
		$lastModified->setTimestamp(1);
		$this->childResponse->setLastModified($lastModified);
		$headers = $this->childResponse->getHeaders();
		$this->assertEquals('Thu, 01 Jan 1970 00:00:01 +0000', $headers['Last-Modified']);
	}

	public function testChainability() {
		$lastModified = new \DateTime(null, new \DateTimeZone('GMT'));
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
		$this->assertEquals('Thu, 01 Jan 1970 00:00:01 +0000', $headers['Last-Modified']);
		$this->assertEquals('max-age=33, must-revalidate',
			$headers['Cache-Control']);

	}

}
