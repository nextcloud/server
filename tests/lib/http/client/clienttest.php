<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Http\Client;

use GuzzleHttp\Message\Response;
use OC\Http\Client\Client;
use OCP\IConfig;

/**
 * Class ClientTest
 */
class ClientTest extends \Test\TestCase {
	/** @var \GuzzleHttp\Client */
	private $guzzleClient;
	/** @var Client */
	private $client;
	/** @var IConfig */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMock('\OCP\IConfig');
		$this->guzzleClient = $this->getMockBuilder('\GuzzleHttp\Client')
			->disableOriginalConstructor()
			->getMock();
		$certificateManager = $this->getMock('\OCP\ICertificateManager');
		$this->client = new Client(
			$this->config,
			$certificateManager,
			$this->guzzleClient
		);
	}

	public function testGetProxyUri() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('proxy', null)
			->willReturn(null);
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('proxyuserpwd', null)
			->willReturn(null);
		$this->assertSame('', self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGetProxyUriProxyHostEmptyPassword() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('proxy', null)
			->willReturn('foo');
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('proxyuserpwd', null)
			->willReturn(null);
		$this->assertSame('foo', self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGetProxyUriProxyHostWithPassword() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('proxy', null)
			->willReturn('foo');
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('proxyuserpwd', null)
			->willReturn('username:password');
		$this->assertSame('username:password@foo', self::invokePrivate($this->client, 'getProxyUri'));
	}

	public function testGet() {
		$this->guzzleClient->method('get')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->get('http://localhost/', [])->getStatusCode());
	}

	public function testPost() {
		$this->guzzleClient->method('post')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->post('http://localhost/', [])->getStatusCode());
	}

	public function testPut() {
		$this->guzzleClient->method('put')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->put('http://localhost/', [])->getStatusCode());
	}

	public function testDelete() {
		$this->guzzleClient->method('delete')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->delete('http://localhost/', [])->getStatusCode());
	}

	public function testOptions() {
		$this->guzzleClient->method('options')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->options('http://localhost/', [])->getStatusCode());
	}
}
