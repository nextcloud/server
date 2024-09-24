<?php

use OC\Files\FileInfo;
use OCA\Files\Helper;
use OCP\ITagManager;
use OCP\ITags;

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
class HelperTest extends \Test\TestCase {
	private function makeFileInfo($name, $size, $mtime, $isDir = false) {
		return new FileInfo(
			'/' . $name,
			null,
			'/',
			[
				'name' => $name,
				'size' => $size,
				'mtime' => $mtime,
				'type' => $isDir ? 'dir' : 'file',
				'mimetype' => $isDir ? 'httpd/unix-directory' : 'application/octet-stream'
			],
			null
		);
	}

	/**
	 * Returns a file list for testing
	 */
	private function getTestFileList() {
		return [
			self::makeFileInfo('a.txt', 4, 2.3 * pow(10, 9)),
			self::makeFileInfo('q.txt', 5, 150),
			self::makeFileInfo('subdir2', 87, 128, true),
			self::makeFileInfo('b.txt', 2.2 * pow(10, 9), 800),
			self::makeFileInfo('o.txt', 12, 100),
			self::makeFileInfo('subdir', 88, 125, true),
		];
	}

	public function sortDataProvider() {
		return [
			[
				'name',
				false,
				['subdir', 'subdir2', 'a.txt', 'b.txt', 'o.txt', 'q.txt'],
			],
			[
				'name',
				true,
				['q.txt', 'o.txt', 'b.txt', 'a.txt', 'subdir2', 'subdir'],
			],
			[
				'size',
				false,
				['a.txt', 'q.txt', 'o.txt', 'subdir2', 'subdir', 'b.txt'],
			],
			[
				'size',
				true,
				['b.txt', 'subdir', 'subdir2', 'o.txt', 'q.txt', 'a.txt'],
			],
			[
				'mtime',
				false,
				['o.txt', 'subdir', 'subdir2', 'q.txt', 'b.txt', 'a.txt'],
			],
			[
				'mtime',
				true,
				['a.txt', 'b.txt', 'q.txt', 'subdir2', 'subdir', 'o.txt'],
			],
		];
	}

	/**
	 * @dataProvider sortDataProvider
	 */
	public function testSortByName(string $sort, bool $sortDescending, array $expectedOrder): void {
		if (($sort === 'mtime') && (PHP_INT_SIZE < 8)) {
			$this->markTestSkipped('Skip mtime sorting on 32bit');
		}
		$files = self::getTestFileList();
		$files = Helper::sortFiles($files, $sort, $sortDescending);
		$fileNames = [];
		foreach ($files as $fileInfo) {
			$fileNames[] = $fileInfo->getName();
		}
		$this->assertEquals(
			$expectedOrder,
			$fileNames
		);
	}

	public function testPopulateTags(): void {
		$tagManager = $this->createMock(ITagManager::class);
		$tagger = $this->createMock(ITags::class);

		$tagManager->method('load')
			->with('files')
			->willReturn($tagger);

		$data = [
			['file_source' => 10],
			['file_source' => 22, 'foo' => 'bar'],
			['file_source' => 42, 'x' => 'y'],
		];

		$tags = [
			10 => ['tag3'],
			42 => ['tag1', 'tag2'],
		];

		$tagger->method('getTagsForObjects')
			->with([10, 22, 42])
			->willReturn($tags);

		$result = Helper::populateTags($data, $tagManager);

		$this->assertSame([
			['file_source' => 10, 'tags' => ['tag3']],
			['file_source' => 22, 'foo' => 'bar', 'tags' => []],
			['file_source' => 42, 'x' => 'y', 'tags' => ['tag1', 'tag2']],
		], $result);
	}
}
