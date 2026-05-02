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

	#[\Override]
	public static function setUpBeforeClass(): void {
		self::$cache = new CappedMemoryCache();
	}

	protected function parserTest($expectedJson, $xmlFile, $cache = null) {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('appDataProvider')]
	public function testApplyL10N(array $data, array $expected, string $language): void {
		$parser = new InfoParser();
		$this->assertSame($expected, $parser->applyL10N($data, $language));
	}

	public static function providesInfoXml(): array {
		return [
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
		$FULL_TRANSLATED = [
			'name' => [
				['@attributes' => ['lang' => 'en'], '@value' => 'App'],
				['@attributes' => ['lang' => 'fr'], '@value' => 'Application']
			],
			'summary' => [
				'Summary',
				['@attributes' => ['lang' => 'fr'], '@value' => 'Résumé']
			],
			'description' => [
				['@attributes' => ['lang' => 'en'], '@value' => 'Description'],
				['@attributes' => ['lang' => 'fr'], '@value' => 'Description (fr)']
			]
		];

		return [
			// test trimming
			[
				['description' => " \t  This is a multiline \n test with \n \t \n \n some new lines   "],
				['description' => "This is a multiline \n test with \n \t \n \n some new lines"],
				'en'
			],
			[
				['description' => " \t  This is a multiline \n test with \n \t   some new lines   "],
				['description' => "This is a multiline \n test with \n \t   some new lines"],
				'en'
			],
			[
				['description' => hex2bin('5065726d657420646520732761757468656e7469666965722064616e732070697769676f20646972656374656d656e74206176656320736573206964656e74696669616e7473206f776e636c6f75642073616e73206c65732072657461706572206574206d657420c3a0206a6f757273206365757820636920656e20636173206465206368616e67656d656e74206465206d6f742064652070617373652e0d0a0d')],
				['description' => "Permet de s'authentifier dans piwigo directement avec ses identifiants owncloud sans les retaper et met à jours ceux ci en cas de changement de mot de passe."],
				'fr'
			],
			// test proper translation handling
			// just strings:
			[
				['name' => 'App', 'summary' => 'Summary', 'description' => 'Description'],
				['name' => 'App', 'summary' => 'Summary', 'description' => 'Description'],
				'en'
			],
			// translated and requesting English:
			[
				$FULL_TRANSLATED,
				['name' => 'App', 'summary' => 'Summary', 'description' => 'Description'],
				'en'
			],
			// translated and requesting translation:
			[
				$FULL_TRANSLATED,
				['name' => 'Application', 'summary' => 'Résumé', 'description' => 'Description (fr)'],
				'fr'
			],
			// translated but requesting non existing translation, should fallback to English:
			[
				$FULL_TRANSLATED,
				['name' => 'App', 'summary' => 'Summary', 'description' => 'Description'],
				'de'
			]
		];
	}
}
