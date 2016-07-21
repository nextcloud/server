<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author brumsel <brumsel@losecatcher.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use OCA\Files\Tests;

/**
 * Class Helper
 */
class HelperTest extends \Test\TestCase {

	private function makeFileInfo($name, $size, $mtime, $isDir = false) {
		return new \OC\Files\FileInfo(
			'/' . $name,
			null,
			'/',
			array(
				'name' => $name,
				'size' => $size,
				'mtime' => $mtime,
				'type' => $isDir ? 'dir' : 'file',
				'mimetype' => $isDir ? 'httpd/unix-directory' : 'application/octet-stream'
			),
			null
		);
	}

	/**
	 * Returns a file list for testing
	 */
	private function getTestFileList() {
		return array(
			self::makeFileInfo('a.txt', 4, 2.3 * pow(10, 9)),
			self::makeFileInfo('q.txt', 5, 150),
			self::makeFileInfo('subdir2', 87, 128, true),
			self::makeFileInfo('b.txt', 2.2 * pow(10, 9), 800),
			self::makeFileInfo('o.txt', 12, 100),
			self::makeFileInfo('subdir', 88, 125, true),
		);
	}

	function sortDataProvider() {
		return array(
			array(
				'name',
				false,
				array('subdir', 'subdir2', 'a.txt', 'b.txt', 'o.txt', 'q.txt'),
			),
			array(
				'name',
				true,
				array('q.txt', 'o.txt', 'b.txt', 'a.txt', 'subdir2', 'subdir'),
			),
			array(
				'size',
				false,
				array('a.txt', 'q.txt', 'o.txt', 'subdir2', 'subdir', 'b.txt'),
			),
			array(
				'size',
				true,
				array('b.txt', 'subdir', 'subdir2', 'o.txt', 'q.txt', 'a.txt'),
			),
			array(
				'mtime',
				false,
				array('o.txt', 'subdir', 'subdir2', 'q.txt', 'b.txt', 'a.txt'),
			),
			array(
				'mtime',
				true,
				array('a.txt', 'b.txt', 'q.txt', 'subdir2', 'subdir', 'o.txt'),
			),
		);
	}

	/**
	 * @dataProvider sortDataProvider
	 */
	public function testSortByName($sort, $sortDescending, $expectedOrder) {
		$files = self::getTestFileList();
		$files = \OCA\Files\Helper::sortFiles($files, $sort, $sortDescending);
		$fileNames = array();
		foreach ($files as $fileInfo) {
			$fileNames[] = $fileInfo->getName();
		}
		$this->assertEquals(
			$expectedOrder,
			$fileNames
		);
	}

}
