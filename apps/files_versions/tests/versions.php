<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ . '/../lib/versions.php';

/**
 * Class Test_Files_versions
 * this class provide basic files versions test
 */
class Test_Files_Versioning extends \PHPUnit_Framework_TestCase {


	/**
	 * @medium
	 * test expire logic
	 * @dataProvider versionsProvider
	 */
	function testGetExpireList($versions, $sizeOfAllDeletedFiles) {

		// last interval enda at 2592000
		$startTime = 5000000;

		$testClass = new VersionStorageToTest();
		list($deleted, $size) = $testClass->callProtectedGetExpireList($startTime, $versions);

		// we should have deleted 16 files each of the size 1
		$this->assertEquals($sizeOfAllDeletedFiles, $size);

		// the deleted array should only contain versions which should be deleted
		foreach($deleted as $key => $path) {
			unset($versions[$key]);
			$this->assertEquals("delete", substr($path, 0, strlen("delete")));
		}

		// the versions array should only contain versions which should be kept
		foreach ($versions as $version) {
			$this->assertEquals("keep", $version['path']);
		}

	}

	public function versionsProvider() {
		return array(
			// first set of versions uniformly distributed versions
			array(
				array(
					// first slice (10sec) keep one version every 2 seconds
					array("version" => 4999999, "path" => "keep", "size" => 1),
					array("version" => 4999998, "path" => "delete", "size" => 1),
					array("version" => 4999997, "path" => "keep", "size" => 1),
					array("version" => 4999995, "path" => "keep", "size" => 1),
					array("version" => 4999994, "path" => "delete", "size" => 1),
					//next slice (60sec) starts at 4999990 keep one version every 10 secons
					array("version" => 4999988, "path" => "keep", "size" => 1),
					array("version" => 4999978, "path" => "keep", "size" => 1),
					array("version" => 4999975, "path" => "delete", "size" => 1),
					array("version" => 4999972, "path" => "delete", "size" => 1),
					array("version" => 4999967, "path" => "keep", "size" => 1),
					array("version" => 4999958, "path" => "delete", "size" => 1),
					array("version" => 4999957, "path" => "keep", "size" => 1),
					//next slice (3600sec) start at 4999940 keep one version every 60 seconds
					array("version" => 4999900, "path" => "keep", "size" => 1),
					array("version" => 4999841, "path" => "delete", "size" => 1),
					array("version" => 4999840, "path" => "keep", "size" => 1),
					array("version" => 4999780, "path" => "keep", "size" => 1),
					array("version" => 4996401, "path" => "keep", "size" => 1),
					// next slice (86400sec) start at 4996400 keep one version every 3600 seconds
					array("version" => 4996350, "path" => "delete", "size" => 1),
					array("version" => 4992800, "path" => "keep", "size" => 1),
					array("version" => 4989800, "path" => "delete", "size" => 1),
					array("version" => 4989700, "path" => "delete", "size" => 1),
					array("version" => 4989200, "path" => "keep", "size" => 1),
					// next slice (2592000sec) start at 4913600 keep one version every 86400 seconds
					array("version" => 4913600, "path" => "keep", "size" => 1),
					array("version" => 4852800, "path" => "delete", "size" => 1),
					array("version" => 4827201, "path" => "delete", "size" => 1),
					array("version" => 4827200, "path" => "keep", "size" => 1),
					array("version" => 4777201, "path" => "delete", "size" => 1),
					array("version" => 4777501, "path" => "delete", "size" => 1),
					array("version" => 4740000, "path" => "keep", "size" => 1),
					// final slice starts at 2408000 keep one version every 604800 secons
					array("version" => 2408000, "path" => "keep", "size" => 1),
					array("version" => 1803201, "path" => "delete", "size" => 1),
					array("version" => 1803200, "path" => "keep", "size" => 1),
					array("version" => 1800199, "path" => "delete", "size" => 1),
					array("version" => 1800100, "path" => "delete", "size" => 1),
					array("version" => 1198300, "path" => "keep", "size" => 1),
				),
				16 // size of all deleted files (every file has the size 1)
			),
			// second set of versions, here we have only really old versions
			array(
				array(
					// first slice (10sec) keep one version every 2 seconds
					// next slice (60sec) starts at 4999990 keep one version every 10 secons
					// next slice (3600sec) start at 4999940 keep one version every 60 seconds
					// next slice (86400sec) start at 4996400 keep one version every 3600 seconds
					array("version" => 4996400, "path" => "keep", "size" => 1),
					array("version" => 4996350, "path" => "delete", "size" => 1),
					array("version" => 4996350, "path" => "delete", "size" => 1),
					array("version" => 4992800, "path" => "keep", "size" => 1),
					array("version" => 4989800, "path" => "delete", "size" => 1),
					array("version" => 4989700, "path" => "delete", "size" => 1),
					array("version" => 4989200, "path" => "keep", "size" => 1),
					// next slice (2592000sec) start at 4913600 keep one version every 86400 seconds
					array("version" => 4913600, "path" => "keep", "size" => 1),
					array("version" => 4852800, "path" => "delete", "size" => 1),
					array("version" => 4827201, "path" => "delete", "size" => 1),
					array("version" => 4827200, "path" => "keep", "size" => 1),
					array("version" => 4777201, "path" => "delete", "size" => 1),
					array("version" => 4777501, "path" => "delete", "size" => 1),
					array("version" => 4740000, "path" => "keep", "size" => 1),
					// final slice starts at 2408000 keep one version every 604800 secons
					array("version" => 2408000, "path" => "keep", "size" => 1),
					array("version" => 1803201, "path" => "delete", "size" => 1),
					array("version" => 1803200, "path" => "keep", "size" => 1),
					array("version" => 1800199, "path" => "delete", "size" => 1),
					array("version" => 1800100, "path" => "delete", "size" => 1),
					array("version" => 1198300, "path" => "keep", "size" => 1),
				),
				11 // size of all deleted files (every file has the size 1)
			),
			// third set of versions, with some gaps inbetween
			array(
				array(
					// first slice (10sec) keep one version every 2 seconds
					array("version" => 4999999, "path" => "keep", "size" => 1),
					array("version" => 4999998, "path" => "delete", "size" => 1),
					array("version" => 4999997, "path" => "keep", "size" => 1),
					array("version" => 4999995, "path" => "keep", "size" => 1),
					array("version" => 4999994, "path" => "delete", "size" => 1),
					//next slice (60sec) starts at 4999990 keep one version every 10 secons
					array("version" => 4999988, "path" => "keep", "size" => 1),
					array("version" => 4999978, "path" => "keep", "size" => 1),
					//next slice (3600sec) start at 4999940 keep one version every 60 seconds
					// next slice (86400sec) start at 4996400 keep one version every 3600 seconds
					array("version" => 4989200, "path" => "keep", "size" => 1),
					// next slice (2592000sec) start at 4913600 keep one version every 86400 seconds
					array("version" => 4913600, "path" => "keep", "size" => 1),
					array("version" => 4852800, "path" => "delete", "size" => 1),
					array("version" => 4827201, "path" => "delete", "size" => 1),
					array("version" => 4827200, "path" => "keep", "size" => 1),
					array("version" => 4777201, "path" => "delete", "size" => 1),
					array("version" => 4777501, "path" => "delete", "size" => 1),
					array("version" => 4740000, "path" => "keep", "size" => 1),
					// final slice starts at 2408000 keep one version every 604800 secons
					array("version" => 2408000, "path" => "keep", "size" => 1),
					array("version" => 1803201, "path" => "delete", "size" => 1),
					array("version" => 1803200, "path" => "keep", "size" => 1),
					array("version" => 1800199, "path" => "delete", "size" => 1),
					array("version" => 1800100, "path" => "delete", "size" => 1),
					array("version" => 1198300, "path" => "keep", "size" => 1),
				),
				9 // size of all deleted files (every file has the size 1)
			),

		);
	}

}

// extend the original class to make it possible to test protected methods
class VersionStorageToTest extends \OCA\Files_Versions\Storage {

	/**
	 * @param integer $time
	 */
	public function callProtectedGetExpireList($time, $versions) {
		return self::getExpireList($time, $versions);

	}
}
