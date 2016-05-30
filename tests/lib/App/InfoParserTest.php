<?php

/**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;
use OCP\IURLGenerator;
use Test\TestCase;

class InfoParserTest extends TestCase {

	/** @var \OC\App\InfoParser */
	private $parser;

	public function setUp() {
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject $urlGenerator */
		$urlGenerator->expects($this->any())
			->method('linkToDocs')
			->will($this->returnCallback(function ($url) {
				return "https://docs.example.com/server/go.php?to=$url";
			}));

		$this->parser = new \OC\App\InfoParser($urlGenerator);
	}

	/**
	 * @dataProvider providesInfoXml
	 */
	public function testParsingValidXml($expectedJson, $xmlFile) {
		$expectedData = null;
		if (!is_null($expectedJson)) {
			$expectedData = json_decode(file_get_contents(OC::$SERVERROOT . "/tests/data/app/$expectedJson"), true);
		}
		$data = $this->parser->parse(OC::$SERVERROOT. "/tests/data/app/$xmlFile");

		$this->assertEquals($expectedData, $data);
	}

	function providesInfoXml() {
		return array(
			array('expected-info.json', 'valid-info.xml'),
			array(null, 'invalid-info.xml'),
		);
	}
}
