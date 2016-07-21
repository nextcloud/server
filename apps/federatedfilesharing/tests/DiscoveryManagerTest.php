<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\FederatedFileSharing\Tests;

use OCA\FederatedFileSharing\DiscoveryManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;

class DiscoveryManagerTest extends \Test\TestCase {
	/** @var ICache */
	private $cache;
	/** @var IClient */
	private $client;
	/** @var DiscoveryManager */
	private $discoveryManager;

	public function setUp() {
		parent::setUp();
		$this->cache = $this->getMock('\OCP\ICache');
		/** @var ICacheFactory $cacheFactory */
		$cacheFactory = $this->getMockBuilder('\OCP\ICacheFactory')
			->disableOriginalConstructor()->getMock();
		$cacheFactory
			->expects($this->once())
			->method('create')
			->with('ocs-discovery')
			->willReturn($this->cache);

		$this->client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		/** @var IClientService $clientService */
		$clientService = $this->getMockBuilder('\OCP\Http\Client\IClientService')
			->disableOriginalConstructor()->getMock();
		$clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($this->client);

		$this->discoveryManager = new DiscoveryManager(
			$cacheFactory,
			$clientService
		);
	}

	public function testWithMalformedFormattedEndpointCached() {
		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('CertainlyNotJson');
		$this->client
			->expects($this->once())
			->method('get')
			->with('https://myhost.com/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			])
			->willReturn($response);
		$this->cache
			->expects($this->at(0))
			->method('get')
			->with('https://myhost.com')
			->willReturn(null);
		$this->cache
			->expects($this->at(1))
			->method('set')
			->with('https://myhost.com', '{"webdav":"\/public.php\/webdav","share":"\/ocs\/v1.php\/cloud\/shares"}');
		$this->cache
			->expects($this->at(2))
			->method('get')
			->with('https://myhost.com')
			->willReturn('{"webdav":"\/public.php\/webdav","share":"\/ocs\/v1.php\/cloud\/shares"}');

		$this->assertSame('/public.php/webdav', $this->discoveryManager->getWebDavEndpoint('https://myhost.com'));
		$this->assertSame('/ocs/v1.php/cloud/shares', $this->discoveryManager->getShareEndpoint('https://myhost.com'));
	}

	public function testGetWebDavEndpointWithValidFormattedEndpointAndNotCached() {
		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('{"version":2,"services":{"PRIVATE_DATA":{"version":1,"endpoints":{"store":"\/ocs\/v2.php\/privatedata\/setattribute","read":"\/ocs\/v2.php\/privatedata\/getattribute","delete":"\/ocs\/v2.php\/privatedata\/deleteattribute"}},"SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/apps\/files_sharing\/api\/v1\/shares"}},"FEDERATED_SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/cloud\/shares","webdav":"\/public.php\/MyCustomEndpoint\/"}},"ACTIVITY":{"version":1,"endpoints":{"list":"\/ocs\/v2.php\/cloud\/activity"}},"PROVISIONING":{"version":1,"endpoints":{"user":"\/ocs\/v2.php\/cloud\/users","groups":"\/ocs\/v2.php\/cloud\/groups","apps":"\/ocs\/v2.php\/cloud\/apps"}}}}');
		$this->client
			->expects($this->once())
			->method('get')
			->with('https://myhost.com/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			])
			->willReturn($response);

		$expectedResult = '/public.php/MyCustomEndpoint/';
		$this->assertSame($expectedResult, $this->discoveryManager->getWebDavEndpoint('https://myhost.com'));
	}

	public function testGetWebDavEndpointWithValidFormattedEndpointWithoutDataAndNotCached() {
		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('{"version":2,"PRIVATE_DATA":{"version":1,"endpoints":{"store":"\/ocs\/v2.php\/privatedata\/setattribute","read":"\/ocs\/v2.php\/privatedata\/getattribute","delete":"\/ocs\/v2.php\/privatedata\/deleteattribute"}},"SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/apps\/files_sharing\/api\/v1\/shares"}},"FEDERATED_SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/cloud\/shares","webdav":"\/public.php\/MyCustomEndpoint\/"}},"ACTIVITY":{"version":1,"endpoints":{"list":"\/ocs\/v2.php\/cloud\/activity"}},"PROVISIONING":{"version":1,"endpoints":{"user":"\/ocs\/v2.php\/cloud\/users","groups":"\/ocs\/v2.php\/cloud\/groups","apps":"\/ocs\/v2.php\/cloud\/apps"}}}');
		$this->client
			->expects($this->once())
			->method('get')
			->with('https://myhost.com/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			])
			->willReturn($response);

		$expectedResult = '/public.php/webdav';
		$this->assertSame($expectedResult, $this->discoveryManager->getWebDavEndpoint('https://myhost.com'));
	}

	public function testGetShareEndpointWithValidFormattedEndpointAndNotCached() {
		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('{"version":2,"services":{"PRIVATE_DATA":{"version":1,"endpoints":{"store":"\/ocs\/v2.php\/privatedata\/setattribute","read":"\/ocs\/v2.php\/privatedata\/getattribute","delete":"\/ocs\/v2.php\/privatedata\/deleteattribute"}},"SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/apps\/files_sharing\/api\/v1\/shares"}},"FEDERATED_SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/cloud\/MyCustomShareEndpoint","webdav":"\/public.php\/MyCustomEndpoint\/"}},"ACTIVITY":{"version":1,"endpoints":{"list":"\/ocs\/v2.php\/cloud\/activity"}},"PROVISIONING":{"version":1,"endpoints":{"user":"\/ocs\/v2.php\/cloud\/users","groups":"\/ocs\/v2.php\/cloud\/groups","apps":"\/ocs\/v2.php\/cloud\/apps"}}}}');
		$this->client
			->expects($this->once())
			->method('get')
			->with('https://myhost.com/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			])
			->willReturn($response);

		$expectedResult = '/ocs/v2.php/cloud/MyCustomShareEndpoint';
		$this->assertSame($expectedResult, $this->discoveryManager->getShareEndpoint('https://myhost.com'));
	}

	public function testWithMaliciousEndpointCached() {
		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('{"version":2,"services":{"PRIVATE_DATA":{"version":1,"endpoints":{"store":"\/ocs\/v2.php\/privatedata\/setattribute","read":"\/ocs\/v2.php\/privatedata\/getattribute","delete":"\/ocs\/v2.php\/privatedata\/deleteattribute"}},"SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/apps\/files_sharing\/api\/v1\/shares"}},"FEDERATED_SHARING":{"version":1,"endpoints":{"share":"\/ocs\/v2.php\/cl@oud\/MyCustomShareEndpoint","webdav":"\/public.php\/MyC:ustomEndpoint\/"}},"ACTIVITY":{"version":1,"endpoints":{"list":"\/ocs\/v2.php\/cloud\/activity"}},"PROVISIONING":{"version":1,"endpoints":{"user":"\/ocs\/v2.php\/cloud\/users","groups":"\/ocs\/v2.php\/cloud\/groups","apps":"\/ocs\/v2.php\/cloud\/apps"}}}}');
		$this->client
			->expects($this->once())
			->method('get')
			->with('https://myhost.com/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			])
			->willReturn($response);
		$this->cache
			->expects($this->at(0))
			->method('get')
			->with('https://myhost.com')
			->willReturn(null);
		$this->cache
			->expects($this->at(1))
			->method('set')
			->with('https://myhost.com', '{"webdav":"\/public.php\/webdav","share":"\/ocs\/v1.php\/cloud\/shares"}');
		$this->cache
			->expects($this->at(2))
			->method('get')
			->with('https://myhost.com')
			->willReturn('{"webdav":"\/public.php\/webdav","share":"\/ocs\/v1.php\/cloud\/shares"}');

		$this->assertSame('/public.php/webdav', $this->discoveryManager->getWebDavEndpoint('https://myhost.com'));
		$this->assertSame('/ocs/v1.php/cloud/shares', $this->discoveryManager->getShareEndpoint('https://myhost.com'));
	}
}
