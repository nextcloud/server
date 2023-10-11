<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace lib\Files\Storage\Wrapper;

use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\KnownMtime;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Test\Files\Storage\Storage;

/**
 * @group DB
 */
class KnownMtimeTest extends Storage {
	/** @var Temporary */
	private $sourceStorage;

	/** @var ClockInterface|MockObject */
	private $clock;
	private int $fakeTime = 0;

	protected function setUp(): void {
		parent::setUp();
		$this->fakeTime = 0;
		$this->sourceStorage = new Temporary([]);
		$this->clock = $this->createMock(ClockInterface::class);
		$this->clock->method('now')->willReturnCallback(function () {
			if ($this->fakeTime) {
				return new \DateTimeImmutable("@{$this->fakeTime}");
			} else {
				return new \DateTimeImmutable();
			}
		});
		$this->instance = $this->getWrappedStorage();
	}

	protected function tearDown(): void {
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	protected function getWrappedStorage() {
		return new KnownMtime([
			'storage' => $this->sourceStorage,
			'clock' => $this->clock,
		]);
	}

	public function testNewerKnownMtime() {
		$future = time() + 1000;
		$this->fakeTime = $future;

		$this->instance->file_put_contents('foo.txt', 'bar');

		// fuzzy match since the clock might have ticked
		$this->assertLessThan(2, abs(time() - $this->sourceStorage->filemtime('foo.txt')));
		$this->assertEquals($this->sourceStorage->filemtime('foo.txt'), $this->sourceStorage->stat('foo.txt')['mtime']);
		$this->assertEquals($this->sourceStorage->filemtime('foo.txt'), $this->sourceStorage->getMetaData('foo.txt')['mtime']);

		$this->assertEquals($future, $this->instance->filemtime('foo.txt'));
		$this->assertEquals($future, $this->instance->stat('foo.txt')['mtime']);
		$this->assertEquals($future, $this->instance->getMetaData('foo.txt')['mtime']);
	}
}
