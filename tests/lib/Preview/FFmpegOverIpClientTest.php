<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\FFmpegOverIpClient;
use Test\TestCase;

/**
 * The server side of the connection is scripted: all its messages are written
 * upfront and the messages of the client are checked afterwards.
 */
class FFmpegOverIpClientTest extends TestCase {
	/** @var resource */
	private $client;
	/** @var resource */
	private $server;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		[$this->client, $this->server] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
	}

	#[\Override]
	protected function tearDown(): void {
		foreach ([$this->client, $this->server] as $socket) {
			if (is_resource($socket)) {
				fclose($socket);
			}
		}
		parent::tearDown();
	}

	private function serverSends(int $type, string $payload = ''): void {
		fwrite($this->server, pack('CN', $type, strlen($payload)) . $payload);
	}

	/**
	 * @return list<array{0: int, 1: string}>
	 */
	private function clientMessages(): array {
		stream_set_blocking($this->server, false);
		$data = stream_get_contents($this->server);
		$messages = [];
		while ($data !== '') {
			$header = unpack('Ctype/Nlength', $data);
			$messages[] = [$header['type'], substr($data, 5, $header['length'])];
			$data = substr($data, 5 + $header['length']);
		}
		return $messages;
	}

	private function communicate(array $files = [], int $timeout = 10, ?FFmpegOverIpClient $client = null): array {
		$client ??= new FFmpegOverIpClient('ffmpeg-over-ip:5050', 'secret', $timeout);
		return $client->communicate($this->client, FFmpegOverIpClient::PROGRAM_FFPROBE, ['-i', '/input.mp4'], $files);
	}

	private function getFiles(string $content): array {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $content);
		return ['/input.mp4' => ['stream' => $stream, 'size' => strlen($content)]];
	}

	public function testSendsSignedCommand(): void {
		$this->serverSends(0x03, pack('N', 0));

		$this->assertSame(['exitCode' => 0, 'stdout' => '', 'stderr' => ''], $this->communicate());

		$messages = $this->clientMessages();
		$this->assertCount(2, $messages);
		[$type, $payload] = $messages[0];
		$this->assertSame(0x01, $type);
		$this->assertSame(0x06, ord($payload[0]));
		$nonce = substr($payload, 1, 16);
		$signature = substr($payload, 17, 32);
		$programAndArgs = substr($payload, 49);
		$this->assertSame("\x02" . pack('n', 2) . pack('n', 2) . '-i' . pack('n', 10) . '/input.mp4', $programAndArgs);
		$expected = hash_hmac('sha256', "\x06" . $nonce . $programAndArgs, 'secret', true);
		$this->assertSame(bin2hex($expected), bin2hex($signature));
		$this->assertSame([0x11, ''], $messages[1]);
	}

	public function testServesRegisteredFile(): void {
		$this->serverSends(0x20, pack('nnNn', 1, 3, 0, 0) . '/input.mp4');
		$this->serverSends(0x23, pack('nnJC', 2, 3, 6, 0));
		$this->serverSends(0x21, pack('nnN', 3, 3, 100));
		$this->serverSends(0x25, pack('nn', 4, 3));
		$this->serverSends(0x23, pack('nnJC', 5, 3, -5, 2));
		$this->serverSends(0x21, pack('nnN', 6, 3, 2));
		$this->serverSends(0x23, pack('nnJC', 7, 3, 1, 1));
		$this->serverSends(0x21, pack('nnN', 8, 3, 1));
		$this->serverSends(0x24, pack('nn', 9, 3));
		$this->serverSends(0x12, 'out');
		$this->serverSends(0x13, 'err');
		$this->serverSends(0x03, pack('N', 0));

		$result = $this->communicate($this->getFiles('Hello world'));

		$this->assertSame(['exitCode' => 0, 'stdout' => 'out', 'stderr' => 'err'], $result);
		$this->assertSame([
			[0x40, pack('nJ', 1, 11)],
			[0x43, pack('nJ', 2, 6)],
			[0x41, pack('n', 3) . 'world'],
			[0x45, pack('nJN', 4, 11, 0o100644)],
			[0x43, pack('nJ', 5, 6)],
			[0x41, pack('n', 6) . 'wo'],
			[0x43, pack('nJ', 7, 9)],
			[0x41, pack('n', 8) . 'l'],
			[0x44, pack('n', 9)],
		], array_slice($this->clientMessages(), 2));
	}

	public function testRejectsOtherFileAccess(): void {
		$this->serverSends(0x20, pack('nnNn', 1, 1, 0, 0) . '/etc/passwd');
		$this->serverSends(0x20, pack('nnNn', 2, 1, 0x0001, 0) . '/input.mp4');
		$this->serverSends(0x20, pack('nnNn', 3, 1, 0x0040, 0644) . '/input.mp4');
		$this->serverSends(0x21, pack('nnN', 4, 1, 100));
		$this->serverSends(0x22, pack('nn', 5, 1) . 'data');
		$this->serverSends(0x27, pack('n', 6) . '/input.mp4');
		$this->serverSends(0x29, pack('nn', 7, 0755) . '/tmp');
		$this->serverSends(0x20, pack('nnNn', 8, 1, 0, 0) . '/input.mp4');
		$this->serverSends(0x20, pack('nnNn', 9, 1, 0, 0) . '/input.mp4');
		$this->serverSends(0x03, pack('N', 1));

		$this->assertSame(1, $this->communicate($this->getFiles('video'))['exitCode']);
		$this->assertSame([
			[0x4F, pack('nN', 1, 2)],
			[0x4F, pack('nN', 2, 30)],
			[0x4F, pack('nN', 3, 30)],
			[0x4F, pack('nN', 4, 22)],
			[0x4F, pack('nN', 5, 30)],
			[0x4F, pack('nN', 6, 30)],
			[0x4F, pack('nN', 7, 30)],
			[0x40, pack('nJ', 8, 5)],
			[0x4F, pack('nN', 9, 22)],
		], array_slice($this->clientMessages(), 2));
	}

	public function testRejectsSeekOverflow(): void {
		$this->serverSends(0x20, pack('nnNn', 1, 1, 0, 0) . '/input.mp4');
		$this->serverSends(0x23, pack('nnJC', 2, 1, PHP_INT_MAX, 2));
		$this->serverSends(0x23, pack('nnJC', 3, 1, -6, 2));
		$this->serverSends(0x21, pack('nnN', 4, 1, 100));
		$this->serverSends(0x03, pack('N', 0));

		$this->communicate($this->getFiles('video'));
		$this->assertSame([
			[0x40, pack('nJ', 1, 5)],
			[0x4F, pack('nN', 2, 22)],
			[0x4F, pack('nN', 3, 22)],
			[0x41, pack('n', 4) . 'video'],
		], array_slice($this->clientMessages(), 2));
	}

	public function testReadLimit(): void {
		$client = new FFmpegOverIpClient('ffmpeg-over-ip:5050', 'secret', 10, null, 8);
		$this->serverSends(0x20, pack('nnNn', 1, 1, 0, 0) . '/input.mp4');
		$this->serverSends(0x21, pack('nnN', 2, 1, 5));
		$this->serverSends(0x21, pack('nnN', 3, 1, 100));
		$this->serverSends(0x03, pack('N', 1));

		$this->communicate($this->getFiles('Hello world'), 10, $client);
		$this->assertSame([
			[0x40, pack('nJ', 1, 11)],
			[0x41, pack('n', 2) . 'Hello'],
			[0x4F, pack('nN', 3, 5)],
		], array_slice($this->clientMessages(), 2));
	}

	public function testFailedSeekDoesNotCorruptLaterReads(): void {
		$content = '';
		for ($i = 0; strlen($content) < 20000; $i++) {
			$content .= sprintf('%08d', $i);
		}
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $content);
		// The stream is shorter than the size, so seeking to the end fails
		$files = ['/input.mp4' => ['stream' => $stream, 'size' => 30000]];

		$this->serverSends(0x20, pack('nnNn', 1, 1, 0, 0) . '/input.mp4');
		$this->serverSends(0x21, pack('nnN', 2, 1, 9000));
		$this->serverSends(0x23, pack('nnJC', 3, 1, 25000, 0));
		$this->serverSends(0x21, pack('nnN', 4, 1, 100));
		$this->serverSends(0x23, pack('nnJC', 5, 1, 9000, 0));
		$this->serverSends(0x21, pack('nnN', 6, 1, 100));
		$this->serverSends(0x23, pack('nnJC', 7, 1, 19990, 0));
		$this->serverSends(0x21, pack('nnN', 8, 1, 100));
		$this->serverSends(0x03, pack('N', 0));

		$this->communicate($files);
		$messages = array_slice($this->clientMessages(), 2);
		$this->assertSame([0x41, pack('n', 2) . substr($content, 0, 9000)], $messages[1]);
		$this->assertSame([0x4F, pack('nN', 4, 5)], $messages[3]);
		$this->assertSame([0x41, pack('n', 6) . substr($content, 9000, 100)], $messages[5]);
		// A short read would be taken as end of file
		$this->assertSame([0x4F, pack('nN', 8, 5)], $messages[7]);
	}

	public function testAnswersPing(): void {
		$this->serverSends(0x05, 'ping');
		$this->serverSends(0x03, pack('N', 0));

		$this->communicate();
		$this->assertSame([0x06, 'ping'], $this->clientMessages()[2]);
	}

	public function testNegativeExitCode(): void {
		$this->serverSends(0x03, pack('N', 0xFFFFFFFF));

		$this->assertSame(-1, $this->communicate()['exitCode']);
	}

	public function testServerError(): void {
		$this->serverSends(0x04, 'authentication failed');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('ffmpeg-over-ip server error: authentication failed');
		$this->communicate();
	}

	public function testConnectionClosed(): void {
		$this->serverSends(0x12, 'partial');
		stream_socket_shutdown($this->server, STREAM_SHUT_WR);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('ffmpeg-over-ip server closed the connection');
		$this->communicate();
	}

	public function testMessageTooLarge(): void {
		fwrite($this->server, pack('CN', 0x12, 2 * 1024 * 1024));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Message from ffmpeg-over-ip server is too large');
		$this->communicate();
	}

	public function testCancelsOnAbort(): void {
		fwrite($this->server, pack('CN', 0x12, 2 * 1024 * 1024));

		try {
			$this->communicate();
			$this->fail('Expected an exception');
		} catch (\RuntimeException) {
		}
		$this->assertSame([0x02, ''], $this->clientMessages()[2]);
	}

	public function testWriteDeadline(): void {
		// The server never reads the response, so the write blocks once the socket buffer is full
		$this->serverSends(0x20, pack('nnNn', 1, 1, 0, 0) . '/input.mp4');
		$this->serverSends(0x21, pack('nnN', 2, 1, 4 * 1024 * 1024));
		$start = microtime(true);

		try {
			$this->communicate($this->getFiles(str_repeat('a', 4 * 1024 * 1024)), 1);
			$this->fail('Expected an exception');
		} catch (\RuntimeException $e) {
			$this->assertSame('ffmpeg-over-ip server did not finish within 1 seconds', $e->getMessage());
		}
		$this->assertLessThan(3, microtime(true) - $start);
	}

	public function testTimeout(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('ffmpeg-over-ip server did not finish within 1 seconds');
		$this->communicate([], 1);
	}

	public function testTimeoutCoversAllRuns(): void {
		$client = new FFmpegOverIpClient('ffmpeg-over-ip:5050', 'secret', 1);
		$this->serverSends(0x03, pack('N', 0));
		$this->communicate([], 1, $client);
		usleep(1100000);
		$this->serverSends(0x03, pack('N', 0));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('ffmpeg-over-ip server did not finish within 1 seconds');
		$this->communicate([], 1, $client);
	}

	public static function invalidAddressProvider(): array {
		return [['http://ffmpeg-over-ip:5050'], ['https://ffmpeg-over-ip:5050/']];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('invalidAddressProvider')]
	public function testInvalidAddress(string $address): void {
		$client = new FFmpegOverIpClient($address, 'secret');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid ffmpeg-over-ip address "' . $address . '", expected host:port, tls://host:port or unix:/path/to/socket');
		$client->run(FFmpegOverIpClient::PROGRAM_FFMPEG, ['-version'], []);
	}

	public static function unreachableAddressProvider(): array {
		return [['127.0.0.1:1'], ['tcp://127.0.0.1:1'], ['tls://127.0.0.1:1'], ['unix:/nonexistent/ffmpeg-over-ip.sock']];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('unreachableAddressProvider')]
	public function testUnreachable(string $address): void {
		$client = new FFmpegOverIpClient($address, 'secret');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(FFmpegOverIpClient::ERROR_CONNECT);
		$this->expectExceptionMessage('Could not connect to ffmpeg-over-ip server ' . $address);
		$client->run(FFmpegOverIpClient::PROGRAM_FFMPEG, ['-version'], []);
	}

	public function testTlsErrorIsReported(): void {
		// A plain server never answers the TLS handshake
		$server = stream_socket_server('tcp://127.0.0.1:0');
		$address = 'tls://' . stream_socket_get_name($server, false);
		$client = new FFmpegOverIpClient($address, 'secret', 1);

		try {
			$client->run(FFmpegOverIpClient::PROGRAM_FFMPEG, ['-version'], []);
			$this->fail('Expected an exception');
		} catch (\RuntimeException $e) {
			$this->assertSame(FFmpegOverIpClient::ERROR_CONNECT, $e->getCode());
			$this->assertMatchesRegularExpression('/^Could not connect to ffmpeg-over-ip server ' . preg_quote($address, '/') . ': \S/', $e->getMessage());
		} finally {
			fclose($server);
		}
	}
}
