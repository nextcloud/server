<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Http\Client;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OC\Http\Client\Client;
use OC\Security\CertificateManager;
use OCP\ICertificateManager;
use OCP\IConfig;

/**
 * Class ClientTest
 */
class ClientTest extends \Test\TestCase {
	/** @var \GuzzleHttp\Client|\PHPUnit_Framework_MockObject_MockObject */
	private $guzzleClient;
	/** @var CertificateManager|\PHPUnit_Framework_MockObject_MockObject */
	private $certificateManager;
	/** @var Client */
	private $client;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->guzzleClient = $this->getMockBuilder('\GuzzleHttp\Client')
			->disableOriginalConstructor()
			->getMock();
		$this->certificateManager = $this->createMock(ICertificateManager::class);
		$this->client = new Client(
			$this->config,
			$this->certificateManager,
			$this->guzzleClient,
			HandlerStack::create()
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
		$this->guzzleClient->method('request')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->get('http://localhost/', [])->getStatusCode());
	}

	public function testPost() {
		$this->guzzleClient->method('request')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->post('http://localhost/', [])->getStatusCode());
	}

	public function testPut() {
		$this->guzzleClient->method('request')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->put('http://localhost/', [])->getStatusCode());
	}

	public function testDelete() {
		$this->guzzleClient->method('request')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->delete('http://localhost/', [])->getStatusCode());
	}

	public function testOptions() {
		$this->guzzleClient->method('request')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->options('http://localhost/', [])->getStatusCode());
	}

	public function testHead() {
		$this->guzzleClient->method('request')
			->willReturn(new Response(1337));
		$this->assertEquals(1337, $this->client->head('http://localhost/', [])->getStatusCode());
	}

	public function testSetDefaultOptionsWithNotInstalled() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('installed', false)
			->willReturn(false);
		$this->certificateManager
			->expects($this->once())
			->method('listCertificates')
			->willReturn([]);

		$this->assertEquals([
			'verify' => \OC::$SERVERROOT . '/resources/config/ca-bundle.crt'
		], self::invokePrivate($this->client, 'getRequestOptions'));
	}

	public function testSetDefaultOptionsWithProxy() {
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
		$this->certificateManager
			->expects($this->once())
			->method('getAbsoluteBundlePath')
			->with(null)
			->willReturn('/my/path.crt');

		$this->assertEquals([
			'verify' => '/my/path.crt',
			'proxy' => 'foo'
		], self::invokePrivate($this->client, 'getRequestOptions'));
	}
}
