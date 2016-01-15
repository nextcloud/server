<?php
/**
 * Copyright (c) 2016 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\L10N;


use OC\L10N\Factory;
use Test\TestCase;

/**
 * Class Test_L10n
 * @group DB
 */
class FactoryTest extends TestCase {
	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	public function setUp() {
		parent::setUp();

		/** @var \OCP\IRequest $request */
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @param array $methods
	 * @return Factory|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getFactory(array $methods = []) {
		/** @var \OCP\IConfig $config */
		$config = $this->getMock('OCP\IConfig');

		if (!empty($methods)) {
			return $this->getMockBuilder('OC\L10N\Factory')
				->setConstructorArgs([
					$config,
					$this->request,
				])
				->setMethods($methods)
				->getMock();
		} else {
			return new Factory($config, $this->request);
		}
	}

	public function dataLanguageExists() {
		return [
			[null, 'en', [], true],
			[null, 'de', [], false],
			[null, 'de', ['ru'], false],
			[null, 'de', ['ru', 'de'], true],
			['files', 'en', [], true],
			['files', 'de', [], false],
			['files', 'de', ['ru'], false],
			['files', 'de', ['de', 'ru'], true],
		];
	}

	/**
	 * @dataProvider dataLanguageExists
	 *
	 * @param string|null $app
	 * @param string $lang
	 * @param string[] $availableLanguages
	 * @param string $expected
	 */
	public function testLanguageExists($app, $lang, array $availableLanguages, $expected) {
		$factory = $this->getFactory(['findAvailableLanguages']);
		$factory->expects(($lang === 'en') ? $this->never() : $this->once())
			->method('findAvailableLanguages')
			->with($app)
			->willReturn($availableLanguages);

		$this->assertSame($expected, $factory->languageExists($app, $lang));
	}

	public function dataSetLanguageFromRequest() {
		return [
			// Language is available
			[null, 'de', null, ['de'], 'de', 'de'],
			[null, 'de,en', null, ['de'], 'de', 'de'],
			[null, 'de-DE,en-US;q=0.8,en;q=0.6', null, ['de'], 'de', 'de'],
			// Language is not available
			[null, 'de', null, ['ru'], 'en', 'en'],
			[null, 'de,en', null, ['ru', 'en'], 'en', 'en'],
			[null, 'de-DE,en-US;q=0.8,en;q=0.6', null, ['ru', 'en'], 'en', 'en'],
			// Language is available, but request language is set
			[null, 'de', 'ru', ['de'], 'de', 'ru'],
			[null, 'de,en', 'ru', ['de'], 'de', 'ru'],
			[null, 'de-DE,en-US;q=0.8,en;q=0.6', 'ru', ['de'], 'de', 'ru'],
		];
	}

	/**
	 * @dataProvider dataSetLanguageFromRequest
	 *
	 * @param string|null $app
	 * @param string $header
	 * @param string|null $requestLanguage
	 * @param string[] $availableLanguages
	 * @param string $expected
	 * @param string $expectedLang
	 */
	public function testSetLanguageFromRequest($app, $header, $requestLanguage, array $availableLanguages, $expected, $expectedLang) {
		$factory = $this->getFactory(['findAvailableLanguages']);
		$factory->expects($this->once())
			->method('findAvailableLanguages')
			->with($app)
			->willReturn($availableLanguages);

		$this->request->expects($this->once())
			->method('getHeader')
			->with('ACCEPT_LANGUAGE')
			->willReturn($header);

		if ($requestLanguage !== null) {
			$this->invokePrivate($factory, 'requestLanguage', [$requestLanguage]);
		}
		$this->assertSame($expected, $factory->setLanguageFromRequest($app), 'Asserting returned language');
		$this->assertSame($expectedLang, $this->invokePrivate($factory, 'requestLanguage'), 'Asserting stored language');
	}

	public function dataGetL10nFilesForApp() {
		return [
			[null, 'de', [\OC::$SERVERROOT . '/core/l10n/de.json']],
			['core', 'ru', [\OC::$SERVERROOT . '/core/l10n/ru.json']],
			['lib', 'ru', [\OC::$SERVERROOT . '/lib/l10n/ru.json']],
			['settings', 'de', [\OC::$SERVERROOT . '/settings/l10n/de.json']],
			['files', 'de', [\OC::$SERVERROOT . '/apps/files/l10n/de.json']],
			['files', '_lang_never_exists_', []],
			['_app_never_exists_', 'de', [\OC::$SERVERROOT . '/core/l10n/de.json']],
		];
	}

	/**
	 * @dataProvider dataGetL10nFilesForApp
	 *
	 * @param string|null $app
	 * @param string $expected
	 */
	public function testGetL10nFilesForApp($app, $lang, $expected) {
		$factory = $this->getFactory();
		$this->assertSame($expected, $this->invokePrivate($factory, 'getL10nFilesForApp', [$app, $lang]));
	}

	public function dataFindL10NDir() {
		return [
			[null, \OC::$SERVERROOT . '/core/l10n/'],
			['core', \OC::$SERVERROOT . '/core/l10n/'],
			['lib', \OC::$SERVERROOT . '/lib/l10n/'],
			['settings', \OC::$SERVERROOT . '/settings/l10n/'],
			['files', \OC::$SERVERROOT . '/apps/files/l10n/'],
			['_app_never_exists_', \OC::$SERVERROOT . '/core/l10n/'],
		];
	}

	/**
	 * @dataProvider dataFindL10NDir
	 *
	 * @param string|null $app
	 * @param string $expected
	 */
	public function testFindL10NDir($app, $expected) {
		$factory = $this->getFactory();
		$this->assertSame($expected, $this->invokePrivate($factory, 'findL10nDir', [$app]));
	}

	public function dataCreatePluralFunction() {
		return [
			['nplurals=2; plural=(n != 1);', 0, 1],
			['nplurals=2; plural=(n != 1);', 1, 0],
			['nplurals=2; plural=(n != 1);', 2, 1],
			['nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;', 0, 2],
			['nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;', 1, 0],
			['nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;', 2, 1],
			['nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;', 3, 1],
			['nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;', 4, 1],
			['nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;', 5, 2],
		];
	}

	/**
	 * @dataProvider dataCreatePluralFunction
	 *
	 * @param string $function
	 * @param int $count
	 * @param int $expected
	 */
	public function testCreatePluralFunction($function, $count, $expected) {
		$factory = $this->getFactory();
		$fn = $factory->createPluralFunction($function);
		$this->assertEquals($expected, $fn($count));
	}
}
