<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\App;

use OC;
use OC\App\InfoParser;
use OCP\Cache\CappedMemoryCache;
use Test\TestCase;

class InfoParserTest extends TestCase {
	private static CappedMemoryCache $cache;

	public static function setUpBeforeClass(): void {
		self::$cache = new CappedMemoryCache();
	}

	public function parserTest($expectedJson, $xmlFile, $cache = null) {
		$parser = new InfoParser($cache);

		$expectedData = null;
		if (!is_null($expectedJson)) {
			$expectedData = json_decode(file_get_contents(OC::$SERVERROOT . "/tests/data/app/$expectedJson"), true);
		}
		$data = $parser->parse(OC::$SERVERROOT . "/tests/data/app/$xmlFile");

		$this->assertEquals($expectedData, $data);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('providesInfoXml')]
	public function testParsingValidXmlWithoutCache($expectedJson, $xmlFile): void {
		$this->parserTest($expectedJson, $xmlFile);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('providesInfoXml')]
	public function testParsingValidXmlWithCache($expectedJson, $xmlFile): void {
		$this->parserTest($expectedJson, $xmlFile, self::$cache);
	}

	public static function providesInfoXml(): array {
		return [
			['expected-info.json', 'valid-info.xml'],
			[null, 'invalid-info.xml'],
			['expected-info.json', 'valid-info.xml'],
			[null, 'invalid-info.xml'],
			['navigation-one-item.json', 'navigation-one-item.xml'],
			['navigation-two-items.json', 'navigation-two-items.xml'],
			['various-single-item.json', 'various-single-item.xml'],
		];
	}

	/**
	 * Providers for the app data values
	 */
	public static function appDataProvider(): array {
		return [
			[
				['description' => " \t  This is a multiline \n test with \n \t \n \n some new lines   "],
				['description' => "This is a multiline \n test with \n \t \n \n some new lines"],
			],
			[
				['description' => " \t  This is a multiline \n test with \n \t   some new lines   "],
				['description' => "This is a multiline \n test with \n \t   some new lines"],
			],
			[
				['description' => hex2bin('5065726d657420646520732761757468656e7469666965722064616e732070697769676f20646972656374656d656e74206176656320736573206964656e74696669616e7473206f776e636c6f75642073616e73206c65732072657461706572206574206d657420c3a0206a6f757273206365757820636920656e20636173206465206368616e67656d656e74206465206d6f742064652070617373652e0d0a0d')],
				['description' => "Permet de s'authentifier dans piwigo directement avec ses identifiants owncloud sans les retaper et met à jours ceux ci en cas de changement de mot de passe."],
			],
			[
				['not-a-description' => " \t  This is a multiline \n test with \n \t   some new lines   "],
				[
					'not-a-description' => " \t  This is a multiline \n test with \n \t   some new lines   ",
					'description' => '',
				],
			],
			[
				['description' => [100, 'bla']],
				['description' => ''],
			],
		];
	}

	/**
	 * Test app info parser
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('appDataProvider')]
	public function testApplyL10NNoLanguage(array $data, array $expected): void {
		$parser = new InfoParser();
		$this->assertSame($expected, $parser->applyL10N($data));
	}

	public function testApplyL10N(): void {
		$parser = new InfoParser();
		$data = $parser->parse(\OC::$SERVERROOT . '/tests/data/app/description-multi-lang.xml');
		$this->assertEquals('English', $parser->applyL10N($data, 'en')['description']);
		$this->assertEquals('German', $parser->applyL10N($data, 'de')['description']);
	}

	public function testApplyL10NSingleLanguage(): void {
		$parser = new InfoParser();
		$data = $parser->parse(\OC::$SERVERROOT . '/tests/data/app/description-single-lang.xml');
		$this->assertEquals('English', $parser->applyL10N($data, 'en')['description']);
	}
}
