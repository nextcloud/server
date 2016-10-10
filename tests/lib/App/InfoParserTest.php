<?php
/**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;
use OC\App\InfoParser;
use Test\TestCase;

class InfoParserTest extends TestCase {
	/** @var OC\Cache\CappedMemoryCache */
	private static $cache;

	public static function setUpBeforeClass() {
		self::$cache = new OC\Cache\CappedMemoryCache();
	}


	public function parserTest($expectedJson, $xmlFile, $cache = null) {
		$parser = new InfoParser($cache);

		$expectedData = null;
		if (!is_null($expectedJson)) {
			$expectedData = json_decode(file_get_contents(OC::$SERVERROOT . "/tests/data/app/$expectedJson"), true);
		}
		$data = $parser->parse(OC::$SERVERROOT. "/tests/data/app/$xmlFile");

		$this->assertEquals($expectedData, $data);
	}

	/**
	 * @dataProvider providesInfoXml
	 */
	public function testParsingValidXmlWithoutCache($expectedJson, $xmlFile) {
		$this->parserTest($expectedJson, $xmlFile);
	}

	/**
	 * @dataProvider providesInfoXml
	 */
	public function testParsingValidXmlWithCache($expectedJson, $xmlFile) {
		$this->parserTest($expectedJson, $xmlFile, self::$cache);
	}

	function providesInfoXml() {
		return array(
			array('expected-info.json', 'valid-info.xml'),
			array(null, 'invalid-info.xml'),
			array('expected-info.json', 'valid-info.xml'),
			array(null, 'invalid-info.xml'),
		);
	}
}
