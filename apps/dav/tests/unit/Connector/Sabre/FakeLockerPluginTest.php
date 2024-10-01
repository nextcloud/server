<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\FakeLockerPlugin;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\Response;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

/**
 * Class FakeLockerPluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class FakeLockerPluginTest extends TestCase {
	/** @var FakeLockerPlugin */
	private $fakeLockerPlugin;

	protected function setUp(): void {
		parent::setUp();
		$this->fakeLockerPlugin = new FakeLockerPlugin();
	}

	public function testInitialize(): void {
		/** @var Server $server */
		$server = $this->getMockBuilder(Server::class)
			->disableOriginalConstructor()
			->getMock();
		$server
			->expects($this->exactly(4))
			->method('on')
			->withConsecutive(
				['method:LOCK', [$this->fakeLockerPlugin, 'fakeLockProvider'], 1],
				['method:UNLOCK', [$this->fakeLockerPlugin, 'fakeUnlockProvider'], 1],
				['propFind', [$this->fakeLockerPlugin, 'propFind']],
				['validateTokens', [$this->fakeLockerPlugin, 'validateTokens']],
			);

		$this->fakeLockerPlugin->initialize($server);
	}

	public function testGetHTTPMethods(): void {
		$expected = [
			'LOCK',
			'UNLOCK',
		];
		$this->assertSame($expected, $this->fakeLockerPlugin->getHTTPMethods('Test'));
	}

	public function testGetFeatures(): void {
		$expected = [
			2,
		];
		$this->assertSame($expected, $this->fakeLockerPlugin->getFeatures());
	}

	public function testPropFind(): void {
		$propFind = $this->getMockBuilder(PropFind::class)
			->disableOriginalConstructor()
			->getMock();
		$node = $this->getMockBuilder(INode::class)
			->disableOriginalConstructor()
			->getMock();

		$propFind->expects($this->exactly(2))
			->method('handle')
			->withConsecutive(
				['{DAV:}supportedlock'],
				['{DAV:}lockdiscovery'],
			);

		$this->fakeLockerPlugin->propFind($propFind, $node);
	}

	public function tokenDataProvider() {
		return [
			[
				[
					[
						'tokens' => [
							[
								'token' => 'aToken',
								'validToken' => false,
							],
							[],
							[
								'token' => 'opaquelocktoken:asdf',
								'validToken' => false,
							]
						],
					]
				],
				[
					[
						'tokens' => [
							[
								'token' => 'aToken',
								'validToken' => false,
							],
							[],
							[
								'token' => 'opaquelocktoken:asdf',
								'validToken' => true,
							]
						],
					]
				],
			]
		];
	}

	/**
	 * @dataProvider tokenDataProvider
	 * @param array $input
	 * @param array $expected
	 */
	public function testValidateTokens(array $input, array $expected): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->fakeLockerPlugin->validateTokens($request, $input);
		$this->assertSame($expected, $input);
	}

	public function testFakeLockProvider(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = new Response();
		$server = $this->getMockBuilder(Server::class)
			->getMock();
		$this->fakeLockerPlugin->initialize($server);

		$request->expects($this->exactly(2))
			->method('getPath')
			->willReturn('MyPath');

		$this->assertSame(false, $this->fakeLockerPlugin->fakeLockProvider($request, $response));

		$expectedXml = '<?xml version="1.0" encoding="utf-8"?><d:prop xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns"><d:lockdiscovery><d:activelock><d:lockscope><d:exclusive/></d:lockscope><d:locktype><d:write/></d:locktype><d:lockroot><d:href>MyPath</d:href></d:lockroot><d:depth>infinity</d:depth><d:timeout>Second-1800</d:timeout><d:locktoken><d:href>opaquelocktoken:fe4f7f2437b151fbcb4e9f5c8118c6b1</d:href></d:locktoken></d:activelock></d:lockdiscovery></d:prop>';

		$this->assertXmlStringEqualsXmlString($expectedXml, $response->getBody());
	}

	public function testFakeUnlockProvider(): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response->expects($this->once())
			->method('setStatus')
			->with('204');
		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Length', '0');

		$this->assertSame(false, $this->fakeLockerPlugin->fakeUnlockProvider($request, $response));
	}
}
