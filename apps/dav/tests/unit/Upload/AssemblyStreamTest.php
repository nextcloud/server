<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Upload\AssemblyStream;
use Sabre\DAV\File;

class AssemblyStreamTest extends \Test\TestCase {

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesNodes')]
	public function testGetContents(string $expected, array $nodeData): void {
		$nodes = [];
		foreach ($nodeData as $data) {
			$nodes[] = $this->buildNode(...$data);
		}
		$stream = AssemblyStream::wrap($nodes);
		$content = stream_get_contents($stream);

		$this->assertEquals($expected, $content);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesNodes')]
	public function testGetContentsFread(string $expected, array $nodeData, int $chunkLength = 3): void {
		$nodes = [];
		foreach ($nodeData as $data) {
			$nodes[] = $this->buildNode(...$data);
		}
		$stream = AssemblyStream::wrap($nodes);

		$content = '';
		while (!feof($stream)) {
			$chunk = fread($stream, $chunkLength);
			$content .= $chunk;
			if ($chunkLength !== 3) {
				$this->assertEquals($chunkLength, strlen($chunk));
			}
		}

		$this->assertEquals($expected, $content);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesNodes')]
	public function testSeek(string $expected, array $nodeData): void {
		$nodes = [];
		foreach ($nodeData as $data) {
			$nodes[] = $this->buildNode(...$data);
		}

		$stream = AssemblyStream::wrap($nodes);

		$offset = floor(strlen($expected) * 0.6);
		if (fseek($stream, $offset) === -1) {
			$this->fail('fseek failed');
		}

		$content = stream_get_contents($stream);
		$this->assertEquals(substr($expected, $offset), $content);
	}

	/**
	 * Reading a node can change the size it reports: Sabre\File::get() notices a
	 * filecache entry that disagrees with the storage, fixes it and refreshes the
	 * node. The short-chunk guard must keep comparing against the size the
	 * assembly was opened with, otherwise a chunk that is short on disk is
	 * accepted and the assembled file is silently truncated.
	 */
	public function testShortNodeIsDetectedWhenItsSizeIsRefreshedWhileReading(): void {
		$nodes = [
			$this->buildNode('0', '12345'),
			$this->buildShrinkingNode('1', 'ab', 5),
			$this->buildNode('2', '67890'),
		];

		$stream = AssemblyStream::wrap($nodes);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Stream from assembly node shorter than expected');
		stream_get_contents($stream);
	}

	public static function providesNodes(): array {
		$data8k = self::makeData(8192);
		$dataLess8k = self::makeData(8191);

		$tonofnodes = [];
		$tonofdata = '';
		for ($i = 0; $i < 101; $i++) {
			$thisdata = random_int(0, 100); // variable length and content
			$tonofdata .= $thisdata;
			$tonofnodes[] = [(string)$i, (string)$thisdata];
		}

		return[
			'one node zero bytes' => [
				'', [
					['0', ''],
				]],
			'one node only' => [
				'1234567890', [
					['0', '1234567890'],
				]],
			'one node buffer boundary' => [
				$data8k, [
					['0', $data8k],
				]],
			'two nodes' => [
				'1234567890', [
					['1', '67890'],
					['0', '12345'],
				]],
			'two nodes end on buffer boundary' => [
				$data8k . $data8k, [
					['1', $data8k],
					['0', $data8k],
				]],
			'two nodes with one on buffer boundary' => [
				$data8k . $dataLess8k, [
					['1', $dataLess8k],
					['0', $data8k],
				]],
			'two nodes on buffer boundary plus one byte' => [
				$data8k . 'X' . $data8k, [
					['1', $data8k],
					['0', $data8k . 'X'],
				]],
			'two nodes on buffer boundary plus one byte at the end' => [
				$data8k . $data8k . 'X', [
					['1', $data8k . 'X'],
					['0', $data8k],
				]],
			'a ton of nodes' => [
				$tonofdata, $tonofnodes
			],
			'one read over multiple nodes' => [
				'1234567890', [
					['0', '1234'],
					['1', '5678'],
					['2', '90'],
				], 10],
			'two reads over multiple nodes' => [
				'1234567890', [
					['0', '1234'],
					['1', '5678'],
					['2', '90'],
				], 5],
		];
	}

	private static function makeData(int $count): string {
		$data = '';
		$base = '1234567890';
		$j = 0;
		for ($i = 0; $i < $count; $i++) {
			$data .= $base[$j];
			$j++;
			if (!isset($base[$j])) {
				$j = 0;
			}
		}
		return $data;
	}

	private function buildNode(string $name, string $data) {
		$node = $this->getMockBuilder(File::class)
			->onlyMethods(['getName', 'get', 'getSize'])
			->getMock();

		$node->expects($this->any())
			->method('getName')
			->willReturn($name);

		$node->expects($this->any())
			->method('get')
			->willReturn($data);

		$node->expects($this->any())
			->method('getSize')
			->willReturn(strlen($data));

		return $node;
	}

	/**
	 * A node that reports the size held in the filecache until it is read, and
	 * its real - smaller - size afterwards, the way Sabre\File::get() behaves
	 * when it repairs a stale cache entry.
	 */
	private function buildShrinkingNode(string $name, string $data, int $cachedSize) {
		$node = $this->getMockBuilder(File::class)
			->onlyMethods(['getName', 'get', 'getSize'])
			->getMock();

		$fetched = false;

		$node->expects($this->any())
			->method('getName')
			->willReturn($name);

		$node->expects($this->any())
			->method('get')
			->willReturnCallback(function () use ($data, &$fetched) {
				$fetched = true;
				return $data;
			});

		$node->expects($this->any())
			->method('getSize')
			->willReturnCallback(function () use ($data, $cachedSize, &$fetched) {
				return $fetched ? strlen($data) : $cachedSize;
			});

		return $node;
	}
}
