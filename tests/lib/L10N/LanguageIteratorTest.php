<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\L10N;

use OC\L10N\LanguageIterator;
use OCP\IConfig;
use OCP\IUser;
use Test\TestCase;

class LanguageIteratorTest extends TestCase {
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var LanguageIterator */
	protected $iterator;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->config = $this->createMock(IConfig::class);

		$this->iterator = new LanguageIterator($this->user, $this->config);
	}

	public function languageSettingsProvider() {
		return [
			// all language settings set
			[ 'de_DE', 'es_CU', 'zh_TW', ['de_DE', 'de', 'es_CU', 'es', 'zh_TW', 'zh', 'en']],
			[ 'de', 'es', 'zh', ['de', 'es', 'zh', 'en']],
			[ 'en', 'en', 'en', ['en', 'en', 'en', 'en']],
			// one possible setting is missing each
			[ false, 'es_CU', 'zh_TW', ['es_CU', 'es', 'zh_TW', 'zh', 'en']],
			[ false, 'es', 'zh_TW', ['es', 'zh_TW', 'zh', 'en']],
			[ false, 'es_CU', 'zh', ['es_CU', 'es', 'zh', 'en']],
			[ 'de_DE', null, 'zh_TW', ['de_DE', 'de', 'zh_TW', 'zh', 'en']],
			[ 'de_DE', null, 'zh', ['de_DE', 'de', 'zh', 'en']],
			[ 'de', null, 'zh_TW', ['de', 'zh_TW', 'zh', 'en']],
			[ 'de_DE', 'es_CU', 'en', ['de_DE', 'de', 'es_CU', 'es', 'en', 'en']],
			[ 'de', 'es_CU', 'en', ['de', 'es_CU', 'es', 'en', 'en']],
			[ 'de_DE', 'es', 'en', ['de_DE', 'de', 'es', 'en', 'en']],
			// two possible settings are missing each
			[ false, null, 'zh_TW', ['zh_TW', 'zh', 'en']],
			[ false, null, 'zh', ['zh', 'en']],
			[ false, 'es_CU', 'en', ['es_CU', 'es', 'en', 'en']],
			[ false, 'es', 'en', ['es', 'en', 'en']],
			[ 'de_DE', null, 'en', ['de_DE', 'de', 'en', 'en']],
			[ 'de', null, 'en', ['de', 'en', 'en']],
			// nothing is set
			[ false, null, 'en', ['en', 'en']],

		];
	}

	/**
	 * @dataProvider languageSettingsProvider
	 */
	public function testIterator($forcedLang, $userLang, $sysLang, $expectedValues): void {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['force_language', false, $forcedLang],
			]);
		$this->config->expects($this->any())
			->method('getSystemValueString')
			->willReturnMap([
				['default_language', 'en', $sysLang],
			]);
		$this->config->expects($this->any())
			->method('getUserValue')
			->willReturn($userLang);

		foreach ($expectedValues as $expected) {
			$this->assertTrue($this->iterator->valid());
			$this->assertSame($expected, $this->iterator->current());
			$this->iterator->next();
		}
		$this->assertFalse($this->iterator->valid());
	}
}
