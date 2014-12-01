<?php

/**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;
use OC\App\Platform;
use OCP\IL10N;

class DependencyAnalyzer extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Platform
	 */
	private $platformMock;

	/**
	 * @var IL10N
	 */
	private $l10nMock;

	public function setUp() {
		$this->platformMock = $this->getMockBuilder('\OC\App\Platform')
			->getMock();
		$this->platformMock->expects($this->any())
			->method('getPhpVersion')
			->will( $this->returnValue('5.4.3'));
		$this->l10nMock = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
	}

	/**
	 * @dataProvider providesPhpVersion
	 */
	public function testPhpVersion($expectedMissing, $minVersion, $maxVersion) {
		$app = array(
			'dependencies' => array(
				'php' => array()
			)
		);
		if (!is_null($minVersion)) {
			$app['dependencies']['php']['min-version'] = $minVersion;
		}
		if (!is_null($maxVersion)) {
			$app['dependencies']['php']['max-version'] = $maxVersion;
		}
		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals(count($expectedMissing), count($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	function providesPhpVersion() {
		return array(
			array(array(), null, null),
			array(array(), '5.4', null),
			array(array(), null, '5.5'),
			array(array(), '5.4', '5.5'),
			array(array('PHP 5.4.4 or higher is required.'), '5.4.4', null),
			array(array('PHP with a version less then 5.4.2 is required.'), null, '5.4.2'),
		);
	}
}
