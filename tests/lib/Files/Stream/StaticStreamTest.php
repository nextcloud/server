<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Stream;

class StaticStreamTest extends \Test\TestCase {

	private $sourceFile;
	private $sourceText;

	protected function setUp() {
		parent::setUp();
		$this->sourceFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->sourceText = file_get_contents($this->sourceFile);
	}

	protected function tearDown() {
		\OC\Files\Stream\StaticStream::clear();
		parent::tearDown();
	}

	public function testContent() {
		file_put_contents('static://foo', $this->sourceText);
		$this->assertEquals($this->sourceText, file_get_contents('static://foo'));
	}

	public function testMultipleFiles() {
		file_put_contents('static://foo', $this->sourceText);
		file_put_contents('static://bar', strrev($this->sourceText));
		$this->assertEquals($this->sourceText, file_get_contents('static://foo'));
		$this->assertEquals(strrev($this->sourceText), file_get_contents('static://bar'));
	}

	public function testOverwrite() {
		file_put_contents('static://foo', $this->sourceText);
		file_put_contents('static://foo', 'qwerty');
		$this->assertEquals('qwerty', file_get_contents('static://foo'));
	}

	public function testIsFile() {
		$this->assertFalse(is_file('static://foo'));
		file_put_contents('static://foo', $this->sourceText);
		$this->assertTrue(is_file('static://foo'));
	}

	public function testIsDir() {
		$this->assertFalse(is_dir('static://foo'));
		file_put_contents('static://foo', $this->sourceText);
		$this->assertFalse(is_dir('static://foo'));
	}

	public function testFileType() {
		file_put_contents('static://foo', $this->sourceText);
		$this->assertEquals('file', filetype('static://foo'));
	}

	public function testUnlink() {
		$this->assertFalse(file_exists('static://foo'));
		file_put_contents('static://foo', $this->sourceText);
		$this->assertTrue(file_exists('static://foo'));
		unlink('static://foo');
		clearstatcache();
		$this->assertFalse(file_exists('static://foo'));
	}
}
