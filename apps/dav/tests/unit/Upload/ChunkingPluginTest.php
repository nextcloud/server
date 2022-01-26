<?php
/**
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Upload\ChunkingPlugin;
use OCA\DAV\Upload\FutureFile;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ChunkingPluginTest extends TestCase {


	/**
	 * @var Tree | MockObject
	 */
	private $tree;

	/**
	 * @var ChunkingPlugin
	 */
	private $plugin;
	/** @var RequestInterface | MockObject */
	private $request;
	/** @var ResponseInterface | MockObject */
	private $response;

	protected function setUp(): void {
		parent::setUp();

		$server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);

		$server->tree = $this->tree;
		$this->plugin = new ChunkingPlugin();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$server->httpRequest = $this->request;
		$server->httpResponse = $this->response;

		$this->plugin->initialize($server);
	}

	/**
	 * @throws BadRequest
	 * @throws NotFound|Forbidden
	 */
	public function testBeforeMoveFutureFileSkip() {
		$node = $this->createMock(Directory::class);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('source')
			->willReturn($node);
		$this->response->expects($this->never())
			->method('setStatus');

		$this->assertNull($this->plugin->beforeMove('source', 'target'));
	}

	/**
	 * @throws NotFound|Forbidden
	 */
	public function testBeforeMoveDestinationIsDirectory() {
		$this->expectException(BadRequest::class);
		$this->expectExceptionMessage('The given destination target is a directory.');

		$sourceNode = $this->createMock(FutureFile::class);
		$targetNode = $this->createMock(Directory::class);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(['source'], ['target'])
			->willReturnOnConsecutiveCalls($sourceNode, $targetNode);
		$this->response->expects($this->never())
			->method('setStatus');

		$this->assertNull($this->plugin->beforeMove('source', 'target'));
	}

	/**
	 * @throws BadRequest
	 * @throws NotFound|Forbidden
	 */
	public function testBeforeMoveFutureFileSkipNonExisting() {
		$sourceNode = $this->createMock(FutureFile::class);
		$sourceNode->expects($this->once())
			->method('getSize')
			->willReturn(4);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(['source'], ['target'])
			->willReturnOnConsecutiveCalls($sourceNode, $this->throwException(new NotFound()));
		$this->tree->expects($this->any())
			->method('nodeExists')
			->with('target')
			->willReturn(false);
		$this->response->expects($this->once())
			->method('setHeader')
			->with('Content-Length', '0');
		$this->response->expects($this->once())
			->method('setStatus')
			->with(201);
		$this->request->expects($this->once())
			->method('getHeader')
			->with('OC-Total-Length')
			->willReturn('4');

		$this->assertFalse($this->plugin->beforeMove('source', 'target'));
	}

	/**
	 * @throws BadRequest
	 * @throws NotFound|Forbidden
	 */
	public function testBeforeMoveFutureFileMoveIt() {
		$sourceNode = $this->createMock(FutureFile::class);
		$sourceNode->expects($this->once())
			->method('getSize')
			->willReturn(4);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(['source'], ['target'])
			->willReturnOnConsecutiveCalls($sourceNode, $this->throwException(new NotFound()));

		$this->tree->expects($this->any())
			->method('nodeExists')
			->with('target')
			->willReturn(true);
		$this->tree->expects($this->once())
			->method('move')
			->with('source', 'target');

		$this->response->expects($this->once())
			->method('setHeader')
			->with('Content-Length', '0');
		$this->response->expects($this->once())
			->method('setStatus')
			->with(204);
		$this->request->expects($this->once())
			->method('getHeader')
			->with('OC-Total-Length')
			->willReturn('4');

		$this->assertFalse($this->plugin->beforeMove('source', 'target'));
	}


	/**
	 * @throws NotFound|Forbidden
	 */
	public function testBeforeMoveSizeIsWrong() {
		$this->expectException(BadRequest::class);
		$this->expectExceptionMessage('Chunks on server do not sum up to 4 but to 3 bytes');

		$sourceNode = $this->createMock(FutureFile::class);
		$sourceNode->expects($this->once())
			->method('getSize')
			->willReturn(3);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(['source'], ['target'])
			->willReturnOnConsecutiveCalls($sourceNode, $this->throwException(new NotFound()));

		$this->request->expects($this->once())
			->method('getHeader')
			->with('OC-Total-Length')
			->willReturn('4');

		$this->assertFalse($this->plugin->beforeMove('source', 'target'));
	}
}
