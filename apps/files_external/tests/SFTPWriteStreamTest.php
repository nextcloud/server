<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\SFTPWriteStream;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the SFTP write stream wrapper.
 *
 * The stream talks to phpseclib's low-level (private) SFTP methods through the
 * SFTPReflection seam. Those two seam methods (invokeSftp / getSftpProperty) are
 * stubbed here so the full framing, buffering, pipelining and drain logic can be
 * exercised deterministically without a live SFTP server.
 */
class SFTPWriteStreamTest extends \Test\TestCase {
	protected function setUp(): void {
		parent::setUp();
		// The stream uses the NET_SFTP_* global constants; phpseclib defines them
		// lazily the first time an SFTP object is constructed (no connection made).
		if (!defined('NET_SFTP_HANDLE')) {
			new SFTP('localhost');
		}
	}

	/**
	 * Build a stream with the SFTP I/O boundary stubbed by a scripted fake server.
	 */
	private function buildStream(\stdClass $state): SFTPWriteStream&MockObject {
		$sftp = $this->createMock(SFTP::class);
		$sftp->method('realpath')->willReturnCallback(fn ($p) => $state->realpath ?? $p);
		$sftp->method('touch')->willReturnCallback(function (...$args) use ($state): bool {
			$state->touchArgs = $args;
			return true;
		});

		$stream = $this->getMockBuilder(SFTPWriteStream::class)
			->onlyMethods(['invokeSftp', 'getSftpProperty'])
			->getMock();

		$stream->method('getSftpProperty')->willReturnCallback(fn ($s, $prop) => match ($prop) {
			'bitmap' => $state->bitmap,
			'packet_type' => $state->packetType,
			'max_sftp_packet' => $state->maxPacket,
			default => null,
		});

		$stream->method('invokeSftp')->willReturnCallback(function ($s, $method, $args = []) use ($state) {
			switch ($method) {
				case 'send_sftp_packet':
					$state->sent[] = ['type' => $args[0], 'packet' => $args[1]];
					if ($state->sendThrowAfter !== null && count($state->sent) > $state->sendThrowAfter) {
						throw new \RuntimeException('send failed');
					}
					return null;
				case 'get_sftp_packet':
					$resp = array_shift($state->getResponses);
					$state->packetType = $resp['type'];
					return $resp['data'];
				case 'read_put_responses':
					$state->readPut[] = $args[0];
					return $state->readPutReturn;
				case 'close_handle':
					$state->closeHandleCalled = true;
					return $state->closeHandleReturn;
				case 'logError':
					$state->logError[] = $args[0];
					return null;
			}
			return null;
		});

		$stream->context = stream_context_create(['sftp' => ['session' => $sftp]]);
		return $stream;
	}

	/**
	 * Default "happy path" fake-server state: logged in, OPEN returns a handle.
	 */
	private function defaultState(int $maxPacket = 32768, string $handle = 'handle'): \stdClass {
		$state = new \stdClass();
		$state->bitmap = SSH2::MASK_LOGIN;
		$state->packetType = null;
		$state->maxPacket = $maxPacket;
		$state->realpath = null;
		$state->sent = [];
		$state->getResponses = [['type' => NET_SFTP_HANDLE, 'data' => pack('N', strlen($handle)) . $handle]];
		$state->readPut = [];
		$state->readPutReturn = true;
		$state->closeHandleCalled = false;
		$state->closeHandleReturn = true;
		$state->logError = [];
		$state->sendThrowAfter = null;
		$state->touchArgs = null;
		return $state;
	}

	/** @return list<array{type: int, packet: string}> */
	private function writePackets(\stdClass $state): array {
		return array_values(array_filter($state->sent, fn ($p) => $p['type'] === NET_SFTP_WRITE));
	}

	private function assertWritePacket(string $packet, string $handle, int $offset, string $data): void {
		$handleLen = unpack('N', substr($packet, 0, 4))[1];
		$pos = 4;
		$this->assertSame($handle, substr($packet, $pos, $handleLen));
		$pos += $handleLen;
		$offHigh = unpack('N', substr($packet, $pos, 4))[1];
		$offLow = unpack('N', substr($packet, $pos + 4, 4))[1];
		$dataLen = unpack('N', substr($packet, $pos + 8, 4))[1];
		$pos += 12;
		$this->assertSame($offset, $offHigh * (2 ** 32) + $offLow, 'offset');
		$this->assertSame($data, substr($packet, $pos, $dataLen), 'data');
	}

	private function openStream(\stdClass $state): SFTPWriteStream&MockObject {
		$stream = $this->buildStream($state);
		$opened = '';
		$this->assertTrue($stream->stream_open('sftpwrite://upload/file.txt', 'w', 0, $opened));
		return $stream;
	}

	// --- register() ---------------------------------------------------------

	public function testRegisterAndDuplicate(): void {
		$proto = 'sftpwritetest' . bin2hex(random_bytes(4));
		$this->assertNotFalse(SFTPWriteStream::register($proto));
		$this->assertFalse(SFTPWriteStream::register($proto));
	}

	// --- loadContext --------------------------------------------------------

	public function testStreamOpenThrowsWhenContextOptionMissing(): void {
		$stream = $this->getMockBuilder(SFTPWriteStream::class)
			->onlyMethods(['invokeSftp', 'getSftpProperty'])
			->getMock();
		$stream->context = stream_context_create([]);

		$this->expectException(\BadMethodCallException::class);
		$opened = '';
		$stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened);
	}

	public function testStreamOpenThrowsWhenSessionMissing(): void {
		$stream = $this->getMockBuilder(SFTPWriteStream::class)
			->onlyMethods(['invokeSftp', 'getSftpProperty'])
			->getMock();
		$stream->context = stream_context_create(['sftp' => ['size' => 1]]);

		$this->expectException(\BadMethodCallException::class);
		$opened = '';
		$stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened);
	}

	// --- stream_open --------------------------------------------------------

	public function testStreamOpenSuccessSendsOpenPacket(): void {
		$state = $this->defaultState();
		$this->openStream($state);

		$this->assertCount(1, $state->sent);
		$this->assertSame(NET_SFTP_OPEN, $state->sent[0]['type']);
	}

	public function testStreamOpenFailsWhenNotLoggedIn(): void {
		$state = $this->defaultState();
		$state->bitmap = 0;
		$stream = $this->buildStream($state);

		$opened = '';
		$this->assertFalse($stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened));
		$this->assertSame([], $state->sent);
	}

	public function testStreamOpenFailsWhenRealpathFails(): void {
		$state = $this->defaultState();
		$state->realpath = false;
		$stream = $this->buildStream($state);

		$opened = '';
		$this->assertFalse($stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened));
		$this->assertSame([], $state->sent);
	}

	public function testStreamOpenReturnsFalseWhenSendThrows(): void {
		$state = $this->defaultState();
		$state->sendThrowAfter = 0; // throw on the first packet (the OPEN)
		$stream = $this->buildStream($state);

		$opened = '';
		$this->assertFalse($stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened));
	}

	public function testStreamOpenLogsErrorOnStatusResponse(): void {
		$state = $this->defaultState();
		$state->getResponses = [['type' => NET_SFTP_STATUS, 'data' => 'permission denied']];
		$stream = $this->buildStream($state);

		$opened = '';
		$this->assertFalse($stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened));
		$this->assertSame(['permission denied'], $state->logError);
	}

	public function testStreamOpenReturnsFalseOnUnexpectedResponse(): void {
		$state = $this->defaultState();
		$state->getResponses = [['type' => 0x7f, 'data' => 'garbage']];
		$stream = $this->buildStream($state);

		set_error_handler(static fn () => true); // swallow the expected E_USER_NOTICE
		try {
			$opened = '';
			$this->assertFalse($stream->stream_open('sftpwrite://upload/f', 'w', 0, $opened));
		} finally {
			restore_error_handler();
		}
	}

	// --- stream_write / pipelining -----------------------------------------

	public function testStreamWriteBuffersBelowPacketSize(): void {
		$state = $this->defaultState(); // packetSize = 32768 - 6 - 25
		$stream = $this->openStream($state);

		$this->assertSame(5, $stream->stream_write('hello'));
		$this->assertSame([], $this->writePackets($state));
		$this->assertSame(5, $stream->stream_tell());
	}

	public function testStreamWriteSendsFullPacketsWithCorrectFraming(): void {
		$state = $this->defaultState(40); // packetSize = 40 - 6 - 25 = 9
		$stream = $this->openStream($state);

		// 13 bytes -> one full 9-byte packet, 4 bytes buffered
		$this->assertSame(13, $stream->stream_write('0123456789ABC'));
		$writes = $this->writePackets($state);
		$this->assertCount(1, $writes);
		$this->assertWritePacket($writes[0]['packet'], 'handle', 0, '012345678');

		// flushing sends the buffered remainder at the advanced offset
		$this->assertTrue($stream->stream_flush());
		$writes = $this->writePackets($state);
		$this->assertCount(2, $writes);
		$this->assertWritePacket($writes[1]['packet'], 'handle', 9, '9ABC');
	}

	public function testWritesAreNotAcknowledgedUntilFlush(): void {
		$state = $this->defaultState(40); // packetSize = 9
		$stream = $this->openStream($state);

		$stream->stream_write(str_repeat('x', 9 * 5)); // five full packets
		$this->assertCount(5, $this->writePackets($state));
		$this->assertSame([], $state->readPut, 'no ACKs drained mid-stream');

		$this->assertTrue($stream->stream_flush());
		$this->assertSame([5], $state->readPut, 'all five drained at flush');
	}

	public function testAcknowledgementsDrainedWhenQueueIsFull(): void {
		$state = $this->defaultState(32); // packetSize = max(1, 32 - 6 - 25) = 1
		$stream = $this->openStream($state);

		// QUEUE_SIZE (1024) one-byte packets must trigger exactly one mid-stream drain
		$stream->stream_write(str_repeat('y', 1024));
		$this->assertSame([1024], $state->readPut);
	}

	public function testStreamWriteReturnsFalseWhenSendFails(): void {
		$state = $this->defaultState(40); // packetSize = 9
		$stream = $this->openStream($state);

		$state->sendThrowAfter = 1; // OPEN already sent (1); next send (a WRITE) throws
		$this->assertFalse($stream->stream_write(str_repeat('z', 9)));
	}

	public function testHighOffsetIsSplitIntoTwoWords(): void {
		$state = $this->defaultState();
		$stream = $this->openStream($state);

		$stream->stream_write('tail'); // buffered (below packet size)
		// Pretend we are already past the 4 GiB boundary to exercise UINT32_MODULUS.
		$prop = new \ReflectionProperty(SFTPWriteStream::class, 'internalPosition');
		$prop->setValue($stream, (2 ** 32) + 5);

		$this->assertTrue($stream->stream_flush());
		$writes = $this->writePackets($state);
		$this->assertWritePacket(end($writes)['packet'], 'handle', (2 ** 32) + 5, 'tail');
	}

	// --- stream_flush -------------------------------------------------------

	public function testStreamFlushWithoutDataOrOutstandingSucceeds(): void {
		$state = $this->defaultState();
		$stream = $this->openStream($state);

		$this->assertTrue($stream->stream_flush());
		$this->assertSame([], $state->readPut);
		$this->assertSame([], $this->writePackets($state));
	}

	public function testStreamFlushReturnsFalseWhenSendFails(): void {
		$state = $this->defaultState(40); // packetSize = 9
		$stream = $this->openStream($state);

		$stream->stream_write(str_repeat('a', 5)); // 5 bytes buffered, nothing sent yet
		$state->sendThrowAfter = 1; // OPEN already sent; the flush's WRITE will throw
		$this->assertFalse($stream->stream_flush());
	}

	public function testStreamFlushReturnsFalseWhenDrainFails(): void {
		$state = $this->defaultState(40);
		$state->readPutReturn = false;
		$stream = $this->openStream($state);

		$stream->stream_write(str_repeat('a', 9)); // one outstanding packet
		$this->assertFalse($stream->stream_flush());
	}

	// --- stream_close -------------------------------------------------------

	public function testStreamCloseFlushesClosesHandleAndTouches(): void {
		$state = $this->defaultState(40);
		$stream = $this->openStream($state);

		$stream->stream_write(str_repeat('b', 5)); // buffered remainder
		$this->assertTrue($stream->stream_close());

		$this->assertTrue($state->closeHandleCalled);
		$this->assertSame([1], $state->readPut, 'remainder flushed then drained');
		$this->assertNotNull($state->touchArgs);
	}

	public function testStreamCloseReturnsFalseWhenCloseHandleFails(): void {
		$state = $this->defaultState();
		$state->closeHandleReturn = false;
		$stream = $this->openStream($state);

		$this->assertFalse($stream->stream_close());
		$this->assertNull($state->touchArgs, 'touch not attempted after failed close');
	}

	// --- stream_tell --------------------------------------------------------

	public function testStreamTellTracksBytesWritten(): void {
		$state = $this->defaultState();
		$stream = $this->openStream($state);

		$stream->stream_write('abc');
		$stream->stream_write('de');
		$this->assertSame(5, $stream->stream_tell());
	}

	// --- read-only / no-op stream operations --------------------------------

	public function testUnsupportedOperationsReturnFalse(): void {
		$stream = $this->buildStream($this->defaultState());

		$this->assertFalse($stream->stream_seek(0));
		$this->assertFalse($stream->stream_read(10));
		$this->assertFalse($stream->stream_set_option(0, 0, 0));
		$this->assertFalse($stream->stream_truncate(0));
		$this->assertFalse($stream->stream_stat());
		$this->assertFalse($stream->stream_lock(LOCK_EX));
		$this->assertFalse($stream->stream_eof());
	}
}
