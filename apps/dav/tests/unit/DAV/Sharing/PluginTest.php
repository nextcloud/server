<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV\Sharing;

use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\DAV\Security\RateLimiting;
use OCA\DAV\DAV\Sharing\IShareable;
use OCA\DAV\DAV\Sharing\Plugin;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PluginTest extends TestCase {
	private Plugin $plugin;
	private Server $server;
	private IShareable&MockObject $book;
	private RateLimiting&MockObject $rateLimiting;

	protected function setUp(): void {
		parent::setUp();

		$authBackend = $this->createMock(Auth::class);
		$authBackend->method('isDavAuthenticated')->willReturn(true);

		$request = $this->createMock(IRequest::class);
		$config = $this->createMock(IConfig::class);
		$this->rateLimiting = $this->createMock(RateLimiting::class);
		$this->plugin = new Plugin($authBackend, $request, $config, $this->rateLimiting);

		$root = new SimpleCollection('root');
		$this->server = new Server($root);
		$this->book = $this->createMock(IShareable::class);
		$this->book->method('getName')->willReturn('addressbook1.vcf');
		$root->addChild($this->book);
		$this->plugin->initialize($this->server);
	}

	public function testSharing(): void {
		$this->rateLimiting->expects(self::once())
			->method('check');
		$this->book->expects(self::once())
			->method('updateShares')
			->with([[
				'href' => 'principal:principals/admin',
				'commonName' => null,
				'summary' => null,
				'readOnly' => false,
			]], ['mailto:wilfredo@example.com']);
		$body = '<?xml version="1.0" encoding="utf-8" ?><CS:share xmlns:D="DAV:" xmlns:CS="http://owncloud.org/ns"><CS:set><D:href>principal:principals/admin</D:href><CS:read-write/></CS:set> <CS:remove><D:href>mailto:wilfredo@example.com</D:href></CS:remove></CS:share>';

		$this->executeRequest($body);
	}

	public function testEmptyShareRequestIsRejected(): void {
		$this->rateLimiting->expects(self::once())
			->method('check');
		$this->book->expects(self::never())
			->method('updateShares');
		$this->expectException(BadRequest::class);
		$this->expectExceptionMessage('{http://owncloud.org/ns}share needs at least one set or remove element');
		$body = '<?xml version="1.0" encoding="utf-8" ?><CS:share xmlns:D="DAV:" xmlns:CS="http://owncloud.org/ns"></CS:share>';

		$this->executeRequest($body);
	}

	public function testShareRequestWithTooManyElementsIsRejected(): void {
		$this->rateLimiting->expects(self::once())
			->method('check');
		$this->book->expects(self::never())
			->method('updateShares');
		$this->expectException(BadRequest::class);
		$this->expectExceptionMessage('{http://owncloud.org/ns}share is limited to 10 set or remove elements');
		$body = '<?xml version="1.0" encoding="utf-8" ?><CS:share xmlns:D="DAV:" xmlns:CS="http://owncloud.org/ns">'
			. '<CS:set><D:href>principal:principals/user1</D:href></CS:set>'
			. '<CS:set><D:href>principal:principals/user2</D:href></CS:set>'
			. '<CS:set><D:href>principal:principals/user3</D:href></CS:set>'
			. '<CS:set><D:href>principal:principals/user4</D:href></CS:set>'
			. '<CS:set><D:href>principal:principals/user5</D:href></CS:set>'
			. '<CS:set><D:href>principal:principals/user6</D:href></CS:set>'
			. '<CS:set><D:href>principal:principals/user7</D:href></CS:set>'
			. '<CS:remove><D:href>principal:principals/user8</D:href></CS:remove>'
			. '<CS:remove><D:href>principal:principals/user9</D:href></CS:remove>'
			. '<CS:remove><D:href>principal:principals/user10</D:href></CS:remove>'
			. '<CS:remove><D:href>principal:principals/user11</D:href></CS:remove>'
			. '<CS:remove><D:href>principal:principals/user12</D:href></CS:remove>'
			. '</CS:share>';

		$this->executeRequest($body);
	}

	private function executeRequest(string $body): void {
		$request = new Request('POST', 'addressbook1.vcf');
		$request->addHeader('Content-Type', 'application/xml');
		$request->setBody($body);
		$response = new Response();

		$this->plugin->httpPost($request, $response);
	}
}
