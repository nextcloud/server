<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Archive;

use OC\Archive\ZIP;

class ZIPTest extends TestBase {
	protected function getExisting() {
		$dir = \OC::$SERVERROOT . '/tests/data';
		return new ZIP($dir . '/data.zip');
	}

	protected function getNew() {
		return new ZIP(\OC::$server->getTempManager()->getTempBaseDir().'/newArchive.zip');
	}

	public function testGetAllFilesStat() {
		$this->instance = $this->getExisting();
		$allStatsIterator = $this->instance->getAllFilesStat();
		$allStats = [];
		foreach ($allStatsIterator as $stat) {
			$allStats[$stat['name']] = $stat;
		}
		$expected = [
			'lorem.txt' => ['mtime' => 1329528294],
			'logo-wide.png' => ['mtime' => 1329528294],
			'dir/' => ['mtime' => 1330699236],
			'dir/lorem.txt' => ['mtime' => 1329528294],
		];
		$count = 0;
		foreach ($expected as $path => $expectedStat) {
			$this->assertTrue(isset($allStats[$path]));
			$stat = $allStats[$path];
			$this->assertEquals($expectedStat['mtime'], $stat['mtime']);
			$this->assertTrue($this->instance->fileExists($stat['name']), 'file ' . $stat['name'] . ' does not exist in archive');
		}
		$this->assertEquals(4, count($allStats), 'only found ' . count($allStats) . ' out of 4 expected files');
	}
}
