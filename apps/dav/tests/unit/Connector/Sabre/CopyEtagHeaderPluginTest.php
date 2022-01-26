<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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

use OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin;
use OCA\DAV\Connector\Sabre\File;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\Http\Request;
use Sabre\Http\Response;
use Test\TestCase;

/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class CopyEtagHeaderPluginTest extends TestCase {

	/** @var CopyEtagHeaderPlugin */
	private $plugin;

	/** @var Server */
	private $server;

	protected function setUp(): void {
		parent::setUp();
		$this->server = new Server();
		$this->plugin = new CopyEtagHeaderPlugin();
		$this->plugin->initialize($this->server);
	}

	public function testCopyEtag() {
		$request = new Request('GET', 'dummy.file');
		$response = new Response();
		$response->setHeader('Etag', 'abcd');

		$this->plugin->afterMethod($request, $response);

		$this->assertEquals('abcd', $response->getHeader('OC-Etag'));
	}

	public function testNoopWhenEmpty() {
		$request = new Request('GET', 'dummy.file');
		$response = new Response();

		$this->plugin->afterMethod($request, $response);

		$this->assertNull($response->getHeader('OC-Etag'));
	}

	public function testAfterMoveNodeNotFound(): void {
		$tree = $this->createMock(Tree::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('test.txt')
			->willThrowException(new NotFound());

		$this->server->tree = $tree;
		$this->plugin->afterMove('', 'test.txt');

		// Nothing to assert, we are just testing if the exception is handled
	}

	public function testAfterMove() {
		$node = $this->createMock(File::class);
		$node->expects($this->once())
			->method('getETag')
			->willReturn('123456');
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with('test.txt')
			->willReturn($node);

		$this->server->tree = $tree;
		$this->plugin->afterMove('', 'test.txt');

		$this->assertEquals('123456', $this->server->httpResponse->getHeader('OC-Etag'));
		$this->assertEquals('123456', $this->server->httpResponse->getHeader('Etag'));
	}
}
