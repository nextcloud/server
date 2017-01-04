<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\ContactExportPlugin;
use Sabre\CardDAV\Card;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ContactExportPluginTest extends TestCase {
	/** @var ResponseInterface | \PHPUnit_Framework_MockObject_MockObject */
	private $response;

	/** @var RequestInterface | \PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var ContactExportPlugin | \PHPUnit_Framework_MockObject_MockObject */
	private $plugin;

	/** @var Server | \PHPUnit_Framework_MockObject_MockObject */
	private $server;

	/** @var Tree | \PHPUnit_Framework_MockObject_MockObject */
	private $tree;

	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$this->server->tree = $this->tree;

		$this->plugin = new ContactExportPlugin();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider providesQueryParams
	 * @param $param
	 */
	public function testQueryParams($param) {
		$this->request->expects($this->once())
			->method('getQueryParameters')
			->willReturn($param);

		$this->response->expects($this->never())
			->method('addHeaders');

		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertNull($result);
	}

	public function providesQueryParams() {
		return [
			[[]],
			[['1']],
			[['foo' => 'bar']],
		];
	}

	public function testNotACard() {
		$this->request->expects($this->once())
			->method('getQueryParameters')
			->willReturn(['export' => true]);
		$this->request->expects($this->once())
			->method('getPath')
			->willReturn('/files/welcome.txt');

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/files/welcome.txt')
			->willReturn(null);

		$this->response->expects($this->never())
			->method('addHeaders');

		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertNull($result);
	}

	public function fullNameProvider() {
		return [
			[100, 'Joanna Doe', 'Joanna Doe.vcf', 'Joanna+Doe.vcf'],
			[101, 'MixУп', 'Mix.vcf', 'Mix%D0%A3%D0%BF.vcf'],
			[110, 'Дшон До', '110.vcf', '%D0%94%D1%88%D0%BE%D0%BD+%D0%94%D0%BE.vcf'],
			[111, '🙉🙈🙊', '111.vcf', '%F0%9F%99%89%F0%9F%99%88%F0%9F%99%8A.vcf']
		];
	}

	/**
	 * @dataProvider fullNameProvider
	 *
	 * @param $fullName
	 * @param $expectedFilename
	 * @param $expectedFileNameUtf8
	 */
	public function testCard($uuid, $fullName, $expectedFilename, $expectedFileNameUtf8) {
		$path = '/files/' . $uuid . '.vcf';
		$expectedContentDisposition =
			'attachment;' .
			'filename*=UTF8\'\''.$expectedFileNameUtf8.';' .
			'filename="'.$expectedFilename.'"'
		;

		$this->request->expects($this->once())
			->method('getQueryParameters')
			->willReturn(['export' => true]);
		$this->request->expects($this->once())
			->method('getPath')
			->willReturn($path);

		$vcard = '
BEGIN:VCARD
VERSION:3.0
FN:'.$fullName.'
UID:'.$uuid.'
REV:20170104T133952Z
END:VCARD'
		;

		$node = $this->createMock(Card::class);
		$node->expects($this->once())
			->method('get')
			->willReturn($vcard);
		$node->expects($this->any())
			->method('getName')
			->willReturn($uuid . '.vcf');

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($path)
			->willReturn($node);

		$this->server->expects($this->once())
			->method('getHTTPHeaders')
			->willReturn([]);

		$this->response->expects($this->once())
			->method('addHeaders')
			->with(['Content-Disposition' => $expectedContentDisposition]);

		$this->plugin->httpGet($this->request, $this->response);
	}

	public function testEmptyCard() {
		$uuid = '1000';
		$path = '/files/' . $uuid . '.vcf';

		$this->request->expects($this->once())
			->method('getQueryParameters')
			->willReturn(['export' => true]);
		$this->request->expects($this->once())
			->method('getPath')
			->willReturn($path);

		$vcard = '
BEGIN:VCARD
VERSION:3.0
UID:'.$uuid.'
REV:20170104T133952Z
END:VCARD'
		;

		$node = $this->createMock(Card::class);
		$node->expects($this->once())
			->method('get')
			->willReturn($vcard);
		$node->expects($this->never())
			->method('getName');

		$this->tree->expects($this->once())
			->method('getNodeForPath')
			->with($path)
			->willReturn($node);

		$this->server->expects($this->never())
			->method('getHTTPHeaders');

		$this->response->expects($this->never())
			->method('addHeaders');

		$this->plugin->httpGet($this->request, $this->response);
	}
}
