<?php

/**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;

class InfoParser extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \OC\App\InfoParser
	 */
	private $parser;

	public function setUp() {
		$httpHelper = $this->getMockBuilder('\OC\HTTPHelper')
			->disableOriginalConstructor()
			->getMock();

		$httpHelper->expects($this->any())
			->method('isHTTPURL')
			->will($this->returnCallback(function ($url) {
				return stripos($url, 'https://') === 0 || stripos($url, 'http://') === 0;
			}));

		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//linkToDocs
		$httpHelper->expects($this->any())
			->method('linkToDocs')
			->will($this->returnCallback(function ($url) {
				return $url;
			}));

		$this->parser = new \OC\App\InfoParser($httpHelper, $urlGenerator);
	}

	public function testParsingValidXml() {
		$data = $this->parser->parse(OC::$SERVERROOT.'/tests/data/app/valid-info.xml');

		$expectedKeys = array(
			'id', 'info', 'remote', 'public', 'name', 'description', 'licence', 'author', 'requiremin', 'shipped',
			'documentation', 'rememberlogin', 'types', 'ocsid'
		);
		foreach($expectedKeys as $expectedKey) {
			$this->assertArrayHasKey($expectedKey, $data, "ExpectedKey($expectedKey) was missing.");
		}
	}
}
