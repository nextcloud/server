<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\FakeLockerPlugin;
use PHPUnit\Framework\MockObject\MockObject;
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

	public function testInitialize() {
		/** @var Server|MockObject $server */
		$server = $this->createMock(Server::class);
		$server
			->expects($this->exactly(4))
			->method('on')
			->withConsecutive(
				['method:LOCK', [$this->fakeLockerPlugin, 'fakeLockProvider'], 1],
				['method:UNLOCK', [$this->fakeLockerPlugin, 'fakeUnlockProvider'], 1],
				['propFind', [$this->fakeLockerPlugin, 'propFind']],
				['validateTokens', [$this->fakeLockerPlugin, 'validateTokens']]
			);

		$this->fakeLockerPlugin->initialize($server);
	}

	public function testGetHTTPMethods() {
		$expected = [
			'LOCK',
			'UNLOCK',
		];
		$this->assertSame($expected, $this->fakeLockerPlugin->getHTTPMethods('Test'));
	}

	public function testGetFeatures() {
		$expected = [
			2,
		];
		$this->assertSame($expected, $this->fakeLockerPlugin->getFeatures());
	}

	public function testPropFind() {
		$propFind = $this->createMock(PropFind::class);
		$node = $this->createMock(INode::class);

		$propFind->expects($this->exactly(2))
			->method('handle')
			->withConsecutive(
				['{DAV:}supportedlock'],
				['{DAV:}lockdiscovery']
			);

		$this->fakeLockerPlugin->propFind($propFind, $node);
	}

	public function tokenDataProvider(): array {
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
	public function testValidateTokens(array $input, array $expected) {
		$request = $this->createMock(RequestInterface::class);
		$this->fakeLockerPlugin->validateTokens($request, $input);
		$this->assertSame($expected, $input);
	}

	public function testFakeLockProvider() {
		$request = $this->createMock(RequestInterface::class);
		$response = new Response();
		$server = $this->getMockBuilder(Server::class)->getMock();
		$this->fakeLockerPlugin->initialize($server);

		$request->expects($this->exactly(2))
			->method('getPath')
			->willReturn('MyPath');

		$this->assertSame(false, $this->fakeLockerPlugin->fakeLockProvider($request, $response));

		$expectedXml = '<?xml version="1.0" encoding="utf-8"?><d:prop xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns"><d:lockdiscovery><d:activelock><d:lockscope><d:exclusive/></d:lockscope><d:locktype><d:write/></d:locktype><d:lockroot><d:href>MyPath</d:href></d:lockroot><d:depth>infinity</d:depth><d:timeout>Second-1800</d:timeout><d:locktoken><d:href>opaquelocktoken:fe4f7f2437b151fbcb4e9f5c8118c6b1</d:href></d:locktoken></d:activelock></d:lockdiscovery></d:prop>';

		$this->assertXmlStringEqualsXmlString($expectedXml, $response->getBody());
	}

	public function testFakeUnlockProvider() {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$response->expects($this->once())
				->method('setStatus')
				->with('204');
		$response->expects($this->once())
				->method('setHeader')
				->with('Content-Length', '0');

		$this->assertSame(false, $this->fakeLockerPlugin->fakeUnlockProvider($request, $response));
	}
}
