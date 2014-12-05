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
			->disableOriginalConstructor()
			->getMock();
		$this->platformMock->expects($this->any())
			->method('getPhpVersion')
			->will( $this->returnValue('5.4.3'));
		$this->platformMock->expects($this->any())
			->method('getDatabase')
			->will( $this->returnValue('mysql'));
		$this->platformMock->expects($this->any())
			->method('getOS')
			->will( $this->returnValue('Linux'));
		$this->platformMock->expects($this->any())
			->method('isCommandKnown')
			->will( $this->returnCallback(function($command) {
				return ($command === 'grep');
			}));
		$this->platformMock->expects($this->any())
			->method('getLibraryVersion')
			->will( $this->returnCallback(function($lib) {
				if ($lib === 'curl') {
					return "2.3.4";
				}
				return null;
			}));
		$this->platformMock->expects($this->any())
			->method('getOcVersion')
			->will( $this->returnValue('8.0.1'));

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
			$app['dependencies']['php']['@attributes']['min-version'] = $minVersion;
		}
		if (!is_null($maxVersion)) {
			$app['dependencies']['php']['@attributes']['max-version'] = $maxVersion;
		}
		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @dataProvider providesDatabases
	 */
	public function testDatabases($expectedMissing, $databases) {
		$app = array(
			'dependencies' => array(
			)
		);
		if (!is_null($databases)) {
			$app['dependencies']['database'] = $databases;
		}
		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @dataProvider providesCommands
	 */
	public function testCommand($expectedMissing, $commands) {
		$app = array(
			'dependencies' => array(
			)
		);
		if (!is_null($commands)) {
			$app['dependencies']['command'] = $commands;
		}
		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @dataProvider providesLibs
	 * @param $expectedMissing
	 * @param $libs
	 */
	function testLibs($expectedMissing, $libs) {
		$app = array(
			'dependencies' => array(
			)
		);
		if (!is_null($libs)) {
			$app['dependencies']['lib'] = $libs;
		}

		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @dataProvider providesOS
	 * @param $expectedMissing
	 * @param $oss
	 */
	function testOS($expectedMissing, $oss) {
		$app = array(
			'dependencies' => array()
		);
		if (!is_null($oss)) {
			$app['dependencies']['os'] = $oss;
		}

		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @dataProvider providesOC
	 * @param $expectedMissing
	 * @param $oc
	 */
	function testOC($expectedMissing, $oc) {
		$app = array(
			'dependencies' => array()
		);
		if (!is_null($oc)) {
			$app['dependencies']['oc'] = $oc;
		}

		$analyser = new \OC\App\DependencyAnalyzer($app, $this->platformMock, $this->l10nMock);
		$missing = $analyser->analyze();

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	function providesOC() {
		return array(
			// no version -> no missing dependency
			array(array(), null),
			array(array('ownCloud 9 or higher is required.'), array('@attributes' => array('min-version' => '9'))),
			array(array('ownCloud with a version lower than 5.1.2 is required.'), array('@attributes' => array('max-version' => '5.1.2'))),
		);
	}

	function providesOS() {
		return array(
			array(array(), null),
			array(array(), array()),
			array(array('Following platforms are supported: WINNT'), array('WINNT'))
		);
	}

	function providesLibs() {
		return array(
			// we expect curl to exist
			array(array(), array('curl')),
			// we expect abcde to exist
			array(array('The library abcde is not available.'), array('abcde')),
			// curl in version 100.0 does not exist
			array(array('Library curl with a version higher than 100.0 is required - available version 2.3.4.'),
				array(array('@attributes' => array('min-version' => '100.0'), '@value' => 'curl'))),
			// curl in version 100.0 does not exist
			array(array('Library curl with a version lower than 1.0.0 is required - available version 2.3.4.'),
				array(array('@attributes' => array('max-version' => '1.0.0'), '@value' => 'curl')))
		);
	}

	function providesCommands() {
		return array(
			array(array(), null),
			// grep is known on linux
			array(array(), array(array('@attributes' => array('os' => 'Linux'), '@value' => 'grep'))),
			// grepp is not known on linux
			array(array('The command line tool grepp could not be found'), array(array('@attributes' => array('os' => 'Linux'), '@value' => 'grepp'))),
			// we don't care about tools on Windows - we are on Linux
			array(array(), array(array('@attributes' => array('os' => 'Windows'), '@value' => 'grepp'))),
			// grep is known on all systems
			array(array(), array('grep')),
		);
	}

	function providesDatabases() {
		return array(
			// non BC - in case on databases are defined -> all are supported
			array(array(), null),
			array(array(), array()),
			array(array('Following databases are supported: sqlite, postgres'), array('sqlite', array('@value' => 'postgres'))),
		);
	}

	function providesPhpVersion() {
		return array(
			array(array(), null, null),
			array(array(), '5.4', null),
			array(array(), null, '5.5'),
			array(array(), '5.4', '5.5'),
			array(array('PHP 5.4.4 or higher is required.'), '5.4.4', null),
			array(array('PHP with a version lower than 5.4.2 is required.'), null, '5.4.2'),
		);
	}
}
