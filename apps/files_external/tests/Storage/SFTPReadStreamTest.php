<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\SFTPReadStream;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the SFTP read stream wrapper.
 *
 * The SFTPReflection seam (invokeSftp / getSftpProperty) is stubbed by a fake
 * server that answers READ requests from an in-memory file, so seeking and
 * end-of-file handling can be exercised without a live SFTP server.
 */
class SFTPReadStreamTest extends \Test\TestCase {
	protected function setUp(): void {
		parent::setUp();
		// The stream uses the NET_SFTP_* global constants; phpseclib defines them
		// lazily the first time an SFTP object is constructed (no connection made).
		if (!defined('NET_SFTP_HANDLE')) {
			new SFTP('localhost');
		}
	}

	private function openStream(string $content): SFTPReadStream&MockObject {
		$state = new \stdClass();
		$state->packetType = null;
		$state->requests = [];

		$sftp = $this->createMock(SFTP::class);
		$sftp->method('realpath')->willReturnCallback(fn ($p) => $p);

		$stream = $this->getMockBuilder(SFTPReadStream::class)
			->onlyMethods(['invokeSftp', 'getSftpProperty'])
			->getMock();

		$stream->method('getSftpProperty')->willReturnCallback(fn ($s, $prop) => match ($prop) {
			'bitmap' => SSH2::MASK_LOGIN,
			'packet_type' => $state->packetType,
			default => null,
		});

		$stream->method('invokeSftp')->willReturnCallback(function ($s, $method, $args = []) use ($state, $content) {
			switch ($method) {
				case 'send_sftp_packet':
					if ($args[0] === NET_SFTP_OPEN) {
						$state->requests[] = ['type' => NET_SFTP_HANDLE, 'data' => pack('N', 6) . 'handle'];
					} elseif ($args[0] === NET_SFTP_READ) {
						$packet = $args[1];
						$handleLength = unpack('N', substr($packet, 0, 4))[1];
						[, $high, $low, $length] = unpack('N3', substr($packet, 4 + $handleLength));
						$offset = $high * (2 ** 32) + $low;
						if ($offset >= strlen($content)) {
							$state->requests[] = ['type' => NET_SFTP_STATUS, 'data' => pack('N', NET_SFTP_STATUS_EOF)];
						} else {
							$state->requests[] = ['type' => NET_SFTP_DATA, 'data' => pack('N', 0) . substr($content, $offset, $length)];
						}
					}
					return null;
				case 'get_sftp_packet':
					$response = array_shift($state->requests);
					$state->packetType = $response['type'];
					return $response['data'];
				case 'close_handle':
					return true;
			}
			return null;
		});

		$stream->context = stream_context_create(['sftp' => ['session' => $sftp, 'size' => strlen($content)]]);
		$opened = '';
		$this->assertTrue($stream->stream_open('sftpread://file.mp4', 'r', 0, $opened));
		return $stream;
	}

	private function readAll(SFTPReadStream $stream): string {
		$data = '';
		while (!$stream->stream_eof()) {
			$data .= $stream->stream_read(8192);
		}
		return $data;
	}

	public function testReadUntilEof(): void {
		$stream = $this->openStream('0123456789');

		$this->assertSame('0123456789', $this->readAll($stream));
		$this->assertTrue($stream->stream_eof());
		$this->assertSame(10, $stream->stream_tell());
	}

	public function testSeekReadsFromTheNewPosition(): void {
		$stream = $this->openStream('0123456789');

		$this->assertTrue($stream->stream_seek(4));
		$this->assertSame(4, $stream->stream_tell());
		$this->assertSame('456789', $this->readAll($stream));
	}

	public function testSeekBackAfterEof(): void {
		$stream = $this->openStream('0123456789');
		$this->readAll($stream);

		// e.g. the movie preview reads the 'moov' atom at the end of a video and then copies the file from the start
		$this->assertTrue($stream->stream_seek(0));
		$this->assertFalse($stream->stream_eof());
		$this->assertSame('0123456789', $this->readAll($stream));
	}
}
