<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Psr7\Utils;
use OC\Http\Client\SizeLimitedSink;
use Psr\Http\Message\StreamInterface;
use Test\TestCase;

class SizeLimitedSinkTest extends TestCase {
	private StreamInterface $target;
	private SizeLimitedSink $sink;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->target = Utils::streamFor();
		$this->sink = new SizeLimitedSink($this->target, 4);
	}

	public function testWritesUpToTheLimitArePassedOn(): void {
		$written = [$this->sink->write('ab'), $this->sink->write('cd')];

		$this->assertSame([2, 2], $written);
		$this->assertFalse($this->sink->isExceeded());
		$this->assertSame('abcd', (string)$this->target);
	}

	public function testWriteBeyondTheLimitIsRefused(): void {
		$this->sink->write('abc');

		$written = $this->sink->write('de');

		$this->assertSame(0, $written);
		$this->assertTrue($this->sink->isExceeded());
		$this->assertSame('abc', (string)$this->target);
	}

	public function testReadsComeFromTheTarget(): void {
		$this->sink->write('abcd');
		$this->sink->rewind();

		$this->assertSame('ab', $this->sink->read(2));
	}
}
