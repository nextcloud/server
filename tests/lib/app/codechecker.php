<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;

class CodeChecker extends \Test\TestCase {

	/**
	 * @dataProvider providesFilesToCheck
	 * @param $expectedErrors
	 * @param $fileToVerify
	 */
	public function testFindInvalidUsage($expectedErrorToken, $expectedErrorCode, $fileToVerify) {
		$checker = new OC\App\CodeChecker();
		$errors = $checker->analyseFile(OC::$SERVERROOT . "/tests/data/app/code-checker/$fileToVerify");

		$this->assertEquals(1, count($errors));
		$this->assertEquals($expectedErrorCode, $errors[0]['errorCode']);
		$this->assertEquals($expectedErrorToken, $errors[0]['disallowedToken']);
	}

	public function providesFilesToCheck() {
		return [
			['OC_Hook', 1000, 'test-extends.php'],
			['oC_Avatar', 1001, 'test-implements.php'],
			['OC_App', 1002, 'test-static-call.php'],
			['OC_API', 1003, 'test-const.php'],
			['OC_AppConfig', 1004, 'test-new.php'],
		];
	}
}
