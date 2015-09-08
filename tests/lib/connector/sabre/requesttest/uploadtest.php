<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre\RequestTest;

use OC\AppFramework\Http;

class UploadTest extends RequestTest {
	public function testBasicUpload() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$this->assertFalse($view->file_exists('foo.txt'));
		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt', 'asd');

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertTrue($view->file_exists('foo.txt'));
		$this->assertEquals('asd', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(3, $info->getSize());
	}

	public function testUploadOverWrite() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'foobar');

		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt', 'asd');

		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());
		$this->assertEquals('asd', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(3, $info->getSize());
	}

	public function testChunkedUpload() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$this->assertFalse($view->file_exists('foo.txt'));
		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt-chunking-123-2-0', 'asd', ['OC-Chunked' => '1']);

		$this->assertEquals(201, $response->getStatus());
		$this->assertFalse($view->file_exists('foo.txt'));

		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt-chunking-123-2-1', 'bar', ['OC-Chunked' => '1']);

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertTrue($view->file_exists('foo.txt'));

		$this->assertEquals('asdbar', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(6, $info->getSize());
	}

	public function testChunkedUploadOverWrite() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');
		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt-chunking-123-2-0', 'asd', ['OC-Chunked' => '1']);

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertEquals('bar', $view->file_get_contents('foo.txt'));

		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt-chunking-123-2-1', 'bar', ['OC-Chunked' => '1']);

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());

		$this->assertEquals('asdbar', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(6, $info->getSize());
	}

	public function testChunkedUploadOutOfOrder() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$this->assertFalse($view->file_exists('foo.txt'));
		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt-chunking-123-2-1', 'bar', ['OC-Chunked' => '1']);

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertFalse($view->file_exists('foo.txt'));

		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt-chunking-123-2-0', 'asd', ['OC-Chunked' => '1']);

		$this->assertEquals(201, $response->getStatus());
		$this->assertTrue($view->file_exists('foo.txt'));

		$this->assertEquals('asdbar', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(6, $info->getSize());
	}
}
