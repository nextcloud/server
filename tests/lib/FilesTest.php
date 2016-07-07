<?php
/**
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
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

namespace Test;

class FilesTest extends \Test\TestCase {

	const UPLOAD_LIMIT_DEFAULT_STR = '513M';
	const UPLOAD_LIMIT_SETTING_STR = '2M';
	const UPLOAD_LIMIT_SETTING_BYTES = 2097152;

	/** @var array $tmpDirs */
	private $tmpDirs = [];

	/**
	 * @return array
	 */
	private function getUploadLimitTestFiles() {
		$dir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->tmpDirs[] = $dir;
		$result = [
			'.htaccess' => $dir . '/htaccess',
			'.user.ini' => $dir . '/user.ini'
		];
		copy(\OC::$SERVERROOT . '/tests/data/setUploadLimit/htaccess', $result['.htaccess']);
		copy(\OC::$SERVERROOT . '/tests/data/setUploadLimit/user.ini', $result['.user.ini']);
		return $result;
	}

	protected function tearDown() {
		foreach ($this->tmpDirs as $dir) {
			\OC_Helper::rmdirr($dir);
		}
		parent::tearDown();
	}

	public function testSetUploadLimitSizeSanity() {
		$this->assertFalse(\OC_Files::setUploadLimit(PHP_INT_MAX + 10));
		$this->assertFalse(\OC_Files::setUploadLimit(\OC_Files::UPLOAD_MIN_LIMIT_BYTES - 10));
		$this->assertFalse(\OC_Files::setUploadLimit('foobar'));
	}

	public function setUploadLimitWriteProvider() {
		return [
			[
				// both files writable
				true, true,
				self::UPLOAD_LIMIT_SETTING_BYTES, self::UPLOAD_LIMIT_SETTING_BYTES,
				self::UPLOAD_LIMIT_SETTING_STR, self::UPLOAD_LIMIT_SETTING_STR
			],
			[
				// neither file writable
				false, false,
				self::UPLOAD_LIMIT_SETTING_BYTES, false,
				self::UPLOAD_LIMIT_DEFAULT_STR, self::UPLOAD_LIMIT_DEFAULT_STR
			],
			[
				// only .htaccess writable
				true, false,
				self::UPLOAD_LIMIT_SETTING_BYTES, false,
				self::UPLOAD_LIMIT_SETTING_STR, self::UPLOAD_LIMIT_DEFAULT_STR
			],
			[
				// only .user.ini writable
				false, true,
				self::UPLOAD_LIMIT_SETTING_BYTES, false,
				self::UPLOAD_LIMIT_DEFAULT_STR, self::UPLOAD_LIMIT_SETTING_STR
			],
			[
				// test rounding of values
				true, true,
				self::UPLOAD_LIMIT_SETTING_BYTES + 20, self::UPLOAD_LIMIT_SETTING_BYTES,
				self::UPLOAD_LIMIT_SETTING_STR, self::UPLOAD_LIMIT_SETTING_STR
			]
		];
	}

	/**
	 * @dataProvider setUploadLimitWriteProvider
	 */
	public function testSetUploadLimitWrite(
		$htaccessWritable, $userIniWritable,
		$setSize, $expectedSize,
		$htaccessStr, $userIniStr
	) {
		$this->markTestSkipped('TODO: Disable because fails on drone');

		$files = $this->getUploadLimitTestFiles();
		chmod($files['.htaccess'], ($htaccessWritable ? 0644 : 0444));
		chmod($files['.user.ini'], ($userIniWritable ? 0644 : 0444));

		$htaccessSize = filesize($files['.htaccess']);
		$userIniSize = filesize($files['.user.ini']);
		$htaccessSizeMod = 2*(strlen($htaccessStr) - strlen(self::UPLOAD_LIMIT_DEFAULT_STR));
		$userIniSizeMod = 2*(strlen($userIniStr) - strlen(self::UPLOAD_LIMIT_DEFAULT_STR));

		$this->assertEquals($expectedSize, \OC_Files::setUploadLimit($setSize, $files));

		// check file contents
		$htaccess = file_get_contents($files['.htaccess']);
		$this->assertEquals(1,
			preg_match('/php_value upload_max_filesize '.$htaccessStr.'/', $htaccess)
		);
		$this->assertEquals(1,
			preg_match('/php_value post_max_size '.$htaccessStr.'/', $htaccess)
		);
		$this->assertEquals($htaccessSize + $htaccessSizeMod, filesize($files['.htaccess']));

		$userIni = file_get_contents($files['.user.ini']);
		$this->assertEquals(1,
			preg_match('/upload_max_filesize='.$userIniStr.'/', $userIni)
		);
		$this->assertEquals(1,
			preg_match('/post_max_size='.$userIniStr.'/', $userIni)
		);
		$this->assertEquals($userIniSize + $userIniSizeMod, filesize($files['.user.ini']));
	}
}
