<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\L10N;

use DateTime;
use OC\L10N\Factory;
use OC\L10N\L10N;
use OCP\App\IAppManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Util;
use Test\TestCase;

/**
 * Class L10nTest
 *
 * @package Test\L10N
 */
class L10nTest extends TestCase {
	/**
	 * @return Factory
	 */
	protected function getFactory() {
		/** @var \OCP\IConfig $config */
		$config = $this->createMock(IConfig::class);
		/** @var \OCP\IRequest $request */
		$request = $this->createMock(IRequest::class);
		/** @var IUserSession $userSession */
		$userSession = $this->createMock(IUserSession::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$appManager = $this->createMock(IAppManager::class);
		return new Factory($config, $request, $userSession, $cacheFactory, \OC::$SERVERROOT, $appManager);
	}

	public function testSimpleTranslationWithTrailingColon(): void {
		$transFile = \OC::$SERVERROOT . '/tests/data/l10n/de.json';
		$l = new L10N($this->getFactory(), 'test', 'de', 'de_AT', [$transFile]);

		$this->assertEquals('Files:', $l->t('Files:'));
	}

	public function testGermanPluralTranslations(): void {
		$transFile = \OC::$SERVERROOT . '/tests/data/l10n/de.json';
		$l = new L10N($this->getFactory(), 'test', 'de', 'de_AT', [$transFile]);

		$this->assertEquals('1 Datei', (string)$l->n('%n file', '%n files', 1));
		$this->assertEquals('2 Dateien', (string)$l->n('%n file', '%n files', 2));
	}

	public function testRussianPluralTranslations(): void {
		$transFile = \OC::$SERVERROOT . '/tests/data/l10n/ru.json';
		$l = new L10N($this->getFactory(), 'test', 'ru', 'ru_UA', [$transFile]);

		$this->assertEquals('1 файл', (string)$l->n('%n file', '%n files', 1));
		$this->assertEquals('2 файла', (string)$l->n('%n file', '%n files', 2));
		$this->assertEquals('6 файлов', (string)$l->n('%n file', '%n files', 6));
		$this->assertEquals('21 файл', (string)$l->n('%n file', '%n files', 21));
		$this->assertEquals('22 файла', (string)$l->n('%n file', '%n files', 22));
		$this->assertEquals('26 файлов', (string)$l->n('%n file', '%n files', 26));

		/*
		  1 file	1 файл	1 папка
		2-4 files	2-4 файла	2-4 папки
		5-20 files	5-20 файлов	5-20 папок
		21 files	21 файл	21 папка
		22-24 files	22-24 файла	22-24 папки
		25-30 files	25-30 файлов	25-30 папок
		etc
		100 files	100 файлов,	100 папок
		1000 files	1000 файлов	1000 папок
		*/
	}

	public function testCzechPluralTranslations(): void {
		$transFile = \OC::$SERVERROOT . '/tests/data/l10n/cs.json';
		$l = new L10N($this->getFactory(), 'test', 'cs', 'cs_CZ', [$transFile]);

		$this->assertEquals('1 okno', (string)$l->n('%n window', '%n windows', 1));
		$this->assertEquals('2 okna', (string)$l->n('%n window', '%n windows', 2));
		$this->assertEquals('5 oken', (string)$l->n('%n window', '%n windows', 5));
	}

	public function testGermanPluralWithCzechLocaleTranslations(): void {
		$transFile = \OC::$SERVERROOT . '/tests/data/l10n/de.json';
		$l = new L10N($this->getFactory(), 'test', 'de', 'cs_CZ', [$transFile]);

		$this->assertEquals('1 Datei', (string)$l->n('%n file', '%n files', 1));
		$this->assertEquals('2 Dateien', (string)$l->n('%n file', '%n files', 2));
		$this->assertEquals('5 Dateien', (string)$l->n('%n file', '%n files', 5));
	}

	public static function dataPlaceholders(): array {
		return [
			['Ordered placeholders one %s two %s', 'Placeholder one 1 two 2'],
			['Reordered placeholders one %s two %s', 'Placeholder two 2 one 1'],
			['Reordered placeholders one %1$s two %2$s', 'Placeholder two 2 one 1'],
		];
	}

	/**
	 * @dataProvider dataPlaceholders
	 *
	 * @param $string
	 * @param $expected
	 */
	public function testPlaceholders($string, $expected): void {
		$transFile = \OC::$SERVERROOT . '/tests/data/l10n/de.json';
		$l = new L10N($this->getFactory(), 'test', 'de', 'de_AT', [$transFile]);

		$this->assertEquals($expected, $l->t($string, ['1', '2']));
	}

	public static function localizationData(): array {
		return [
			// timestamp as string
			["February 13, 2009, 11:31:30\xE2\x80\xAFPM UTC", 'en', 'en_US', 'datetime', '1234567890'],
			['13. Februar 2009, 23:31:30 UTC', 'de', 'de_DE', 'datetime', '1234567890'],
			['February 13, 2009', 'en', 'en_US', 'date', '1234567890'],
			['13. Februar 2009', 'de', 'de_DE', 'date', '1234567890'],
			["11:31:30\xE2\x80\xAFPM UTC", 'en', 'en_US', 'time', '1234567890'],
			['23:31:30 UTC', 'de', 'de_DE', 'time', '1234567890'],

			// timestamp as int
			["February 13, 2009, 11:31:30\xE2\x80\xAFPM UTC", 'en', 'en_US', 'datetime', 1234567890],
			['13. Februar 2009, 23:31:30 UTC', 'de', 'de_DE', 'datetime', 1234567890],
			['February 13, 2009', 'en', 'en_US', 'date', 1234567890],
			['13. Februar 2009', 'de', 'de_DE', 'date', 1234567890],
			["11:31:30\xE2\x80\xAFPM UTC", 'en', 'en_US', 'time', 1234567890],
			['23:31:30 UTC', 'de', 'de_DE', 'time', 1234567890],

			// DateTime object
			["February 13, 2009, 11:31:30\xE2\x80\xAFPM GMT+0", 'en', 'en_US', 'datetime', new DateTime('@1234567890')],
			['13. Februar 2009, 23:31:30 GMT+0', 'de', 'de_DE', 'datetime', new DateTime('@1234567890')],
			['February 13, 2009', 'en', 'en_US', 'date', new DateTime('@1234567890')],
			['13. Februar 2009', 'de', 'de_DE', 'date', new DateTime('@1234567890')],
			["11:31:30\xE2\x80\xAFPM GMT+0", 'en', 'en_US', 'time', new DateTime('@1234567890')],
			['23:31:30 GMT+0', 'de', 'de_DE', 'time', new DateTime('@1234567890')],

			// en_GB
			['13 February 2009, 23:31:30 GMT+0', 'en_GB', 'en_GB', 'datetime', new DateTime('@1234567890')],
			['13 February 2009', 'en_GB', 'en_GB', 'date', new DateTime('@1234567890')],
			['23:31:30 GMT+0', 'en_GB', 'en_GB', 'time', new DateTime('@1234567890')],
			['13 February 2009, 23:31:30 GMT+0', 'en-GB', 'en_GB', 'datetime', new DateTime('@1234567890')],
			['13 February 2009', 'en-GB', 'en_GB', 'date', new DateTime('@1234567890')],
			['23:31:30 GMT+0', 'en-GB', 'en_GB', 'time', new DateTime('@1234567890')],
		];
	}

	/**
	 * @dataProvider localizationData
	 */
	public function testNumericStringLocalization($expectedDate, $lang, $locale, $type, $value): void {
		$l = new L10N($this->getFactory(), 'test', $lang, $locale, []);
		$this->assertSame($expectedDate, $l->l($type, $value));
	}

	public static function firstDayData(): array {
		return [
			[1, 'de', 'de_DE'],
			[0, 'en', 'en_US'],
		];
	}

	/**
	 * @dataProvider firstDayData
	 * @param $expected
	 * @param $lang
	 * @param $locale
	 */
	public function testFirstWeekDay($expected, $lang, $locale): void {
		$l = new L10N($this->getFactory(), 'test', $lang, $locale, []);
		$this->assertSame($expected, $l->l('firstday', 'firstday'));
	}

	public static function jsDateData(): array {
		return [
			['dd.MM.yy', 'de', 'de_DE'],
			['M/d/yy', 'en', 'en_US'],
		];
	}

	/**
	 * @dataProvider jsDateData
	 * @param $expected
	 * @param $lang
	 * @param $locale
	 */
	public function testJSDate($expected, $lang, $locale): void {
		$l = new L10N($this->getFactory(), 'test', $lang, $locale, []);
		$this->assertSame($expected, $l->l('jsdate', 'jsdate'));
	}

	public function testFactoryGetLanguageCode(): void {
		$l = $this->getFactory()->get('lib', 'de');
		$this->assertEquals('de', $l->getLanguageCode());
	}

	public function testServiceGetLanguageCode(): void {
		$l = Util::getL10N('lib', 'de');
		$this->assertEquals('de', $l->getLanguageCode());
	}

	public function testWeekdayName(): void {
		$l = Util::getL10N('lib', 'de');
		$this->assertEquals('Mo.', $l->l('weekdayName', new \DateTime('2017-11-6'), ['width' => 'abbreviated']));
	}

	/**
	 * @dataProvider findLanguageFromLocaleData
	 * @param $locale
	 * @param $language
	 */
	public function testFindLanguageFromLocale($locale, $language): void {
		$this->assertEquals(
			$language,
			Server::get(IFactory::class)->findLanguageFromLocale('lib', $locale)
		);
	}

	public static function findLanguageFromLocaleData(): array {
		return [
			'en_US' => ['en_US', 'en'],
			'en_UK' => ['en_UK', 'en'],
			'de_DE' => ['de_DE', 'de_DE'],
			'de_AT' => ['de_AT', 'de'],
			'es_EC' => ['es_EC', 'es_EC'],
			'fi_FI' => ['fi_FI', 'fi'],
			'zh_CN' => ['zh_CN', 'zh_CN'],
		];
	}
}
