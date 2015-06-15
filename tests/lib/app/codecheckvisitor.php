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

class CodeCheckVisitor extends TestCase {

	public function providesFilesToCheck() {
		return [
			['OCP\AppFramework\IApi', 1006, 'test-deprecated-use.php'],
			['OCP\AppFramework\IApi', 1006, 'test-deprecated-use-alias.php'],
			['AppFramework\IApi', 1001, 'test-deprecated-use-sub.php'],
			['OAF\IApi', 1001, 'test-deprecated-use-sub-alias.php'],
		];
	}

	/**
	 * @dataProvider providesFilesToCheck
	 * @param string $expectedErrorToken
	 * @param int $expectedErrorCode
	 * @param string $fileToVerify
	 */
	public function testFindInvalidUsage($expectedErrorToken, $expectedErrorCode, $fileToVerify) {
		$checker = new \Test\App\Mock\CodeChecker();
		$errors = $checker->analyseFile(OC::$SERVERROOT . "/tests/data/app/code-checker/$fileToVerify");

		$this->assertEquals(1, count($errors));
		$this->assertEquals($expectedErrorCode, $errors[0]['errorCode']);
		$this->assertEquals($expectedErrorToken, $errors[0]['disallowedToken']);
	}

	public function providesConstantsToCheck() {
		return [
			['OCP\NamespaceName\ClassName::CONSTANT_NAME', 1003, 'test-deprecated-constant.php'],
			['Constant::CONSTANT_NAME', 1003, 'test-deprecated-constant-alias.php'],
			['NamespaceName\ClassName::CONSTANT_NAME', 1003, 'test-deprecated-constant-sub.php'],
			['Constant\ClassName::CONSTANT_NAME', 1003, 'test-deprecated-constant-sub-alias.php'],
		];
	}

	/**
	 * @dataProvider providesConstantsToCheck
	 * @param string $expectedErrorToken
	 * @param int $expectedErrorCode
	 * @param string $fileToVerify
	 */
	public function testConstantsToCheck($expectedErrorToken, $expectedErrorCode, $fileToVerify) {
		$checker = new \Test\App\Mock\CodeChecker();
		$errors = $checker->analyseFile(OC::$SERVERROOT . "/tests/data/app/code-checker/$fileToVerify");

		$this->assertEquals(1, count($errors));
		$this->assertEquals($expectedErrorCode, $errors[0]['errorCode']);
		$this->assertEquals($expectedErrorToken, $errors[0]['disallowedToken']);
	}
}
