<?php
/**
 * Copyright (c) 2015 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;
use Test\TestCase;

class DeprecationCodeChecker extends TestCase {

	/**
	 * @dataProvider providesFilesToCheck
	 * @param $expectedErrorToken
	 * @param $expectedErrorCode
	 * @param $fileToVerify
	 */
	public function testFindInvalidUsage($expectedErrorToken, $expectedErrorCode, $fileToVerify) {
		$checker = new \OC\App\DeprecationCodeChecker();
		$errors = $checker->analyseFile(OC::$SERVERROOT . "/tests/data/app/code-checker/$fileToVerify");

		$this->assertEquals(1, count($errors));
		$this->assertEquals($expectedErrorCode, $errors[0]['errorCode']);
		$this->assertEquals($expectedErrorToken, $errors[0]['disallowedToken']);
	}

	public function providesFilesToCheck() {
		return [
			['==', 1005, 'test-equal.php'],
			['!=', 1005, 'test-not-equal.php'],
		];
	}

	/**
	 * @dataProvider validFilesData
	 * @param $fileToVerify
	 */
	public function testPassValidUsage($fileToVerify) {
		$checker = new \OC\App\DeprecationCodeChecker();
		$errors = $checker->analyseFile(OC::$SERVERROOT . "/tests/data/app/code-checker/$fileToVerify");

		$this->assertEquals(0, count($errors));
	}

	public function validFilesData() {
		return [
			['test-extends.php'],
			['test-implements.php'],
			['test-static-call.php'],
			['test-const.php'],
			['test-new.php'],
			['test-identical-operator.php'],
		];
	}
}
