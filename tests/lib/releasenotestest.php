<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace Test;

class ReleaseNotesTest extends \Test\TestCase {

	/**
	 * @param bool $isMysql
	 * @param int $fileCount
	 * @return \PHPUnit_Framework_MockObject_MockObject|\OC\ReleaseNotes
	 */
	protected function getReleaseNotesMock($isMysql, $fileCount) {
		$query = $this->getMockBuilder('OCP\DB\QueryBuilder\IQueryBuilder')
			->disableOriginalConstructor()
			->getMock();
		$query->expects($this->any())
			->method('getTableName')
			->willReturnCallback(function($tableName) {
				return 'ocx_' . $tableName;
			});

		$dbConnectionMock = $this->getMockBuilder('OCP\IDBConnection')
			->disableOriginalConstructor()
			->getMock();
		$dbConnectionMock->expects($this->any())
			->method('getQueryBuilder')
			->willReturn($query);
		$releaseNotesMock = $this->getMockBuilder('OC\ReleaseNotes')
			->setConstructorArgs([$dbConnectionMock])
			->setMethods(['isMysql', 'countFilecacheEntries'])
			->getMock();

		$releaseNotesMock->expects($this->any())
			->method('isMysql')
			->willReturn($isMysql);
		$releaseNotesMock->expects($this->any())
			->method('countFilecacheEntries')
			->willReturn($fileCount);
		return $releaseNotesMock;
	}

	public function data82to90() {
		return [
			[[], false, 20],
			[[], true, 20],
			[[], false, 1000000],
			[['Hint: You can speed up the upgrade by executing this SQL command manually: ALTER TABLE ocx_filecache ADD COLUMN checksum varchar(255) DEFAULT NULL AFTER permissions;'], true, 1000000],
		];
	}

	/**
	 * @dataProvider data82to90
	 *
	 * @param string[] $expected
	 * @param bool $isMysql
	 * @param int $fileCount
	 */
	public function test82to90($expected, $isMysql, $fileCount) {
		$releaseNotesMock = $this->getReleaseNotesMock($isMysql, $fileCount);
		$actual = $releaseNotesMock->getReleaseNotes('8.2.22', '9.0.1');
		$this->assertEquals($expected, $actual);
	}

	public function data90to91() {
		return [
			[false, 20],
			[true, 20],
			[false, 1000000],
			[true, 1000000],
		];
	}

	/**
	 * @dataProvider data90to91
	 *
	 * @param bool $isMysql
	 * @param int $fileCount
	 */
	public function test90to91($isMysql, $fileCount) {
		$releaseNotesMock = $this->getReleaseNotesMock($isMysql, $fileCount);
		$actual = $releaseNotesMock->getReleaseNotes('9.0.1', '9.1.0');
		$this->assertCount(0, $actual);
	}
}
