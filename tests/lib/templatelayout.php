<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Test;

/**
 * @package OC\Test
 */
class OC_TemplateLayout extends \PHPUnit_Framework_TestCase {

	/**
	 * Contains valid file paths in the scheme array($absolutePath, $expectedPath)
	 * @return array
	 */
	public function validFilePathProvider() {
		return array(
			array(\OC::$SERVERROOT . '/apps/files/js/fancyJS.js', '/apps/files/js/fancyJS.js'),
			array(\OC::$SERVERROOT. '/test.js', '/test.js'),
			array(\OC::$SERVERROOT . '/core/test.js', '/core/test.js'),
			array(\OC::$SERVERROOT, ''),
		);
	}

	/**
	 * @dataProvider validFilePathProvider
	 */
	public function testConvertToRelativePath($absolutePath, $expected) {
		$_SERVER['REQUEST_URI'] = $expected;
		$_SERVER['SCRIPT_NAME'] = '/';

		$relativePath = \Test_Helper::invokePrivate(new \OC_TemplateLayout('user'), 'convertToRelativePath', array($absolutePath));
		$this->assertEquals($expected, $relativePath);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage $filePath is not under the \OC::$SERVERROOT
	 */
	public function testInvalidConvertToRelativePath() {
		$invalidFile = '/this/file/is/invalid';
		$_SERVER['REQUEST_URI'] = $invalidFile;
		$_SERVER['SCRIPT_NAME'] = '/';

		\Test_Helper::invokePrivate(new \OC_TemplateLayout('user'), 'convertToRelativePath', array($invalidFile));
	}
}
