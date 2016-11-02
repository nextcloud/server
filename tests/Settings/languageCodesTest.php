<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tests\Settings;

use Test\TestCase;

class languageCodesTest extends TestCase  {
	public function test() {
		$languageArray = include_once __DIR__ . '/../../settings/languageCodes.php';

		$expected = [
			'el'=>'Ελληνικά',
			'en'=>'English',
			'fa'=>'فارسى',
			'fi_FI'=>'Suomi',
			'hi'=>'हिन्दी',
			'id'=>'Bahasa Indonesia',
			'lb'=>'Lëtzebuergesch',
			'ms_MY'=>'Bahasa Melayu',
			'nb_NO'=>'Norwegian Bokmål',
			'pt_BR'=>'Português brasileiro',
			'pt_PT'=>'Português',
			'ro'=>'română',
			'sr@latin'=>'Srpski',
			'sv'=>'Svenska',
			'hu_HU'=>'Magyar',
			'hr'=>'Hrvatski',
			'ar'=>'العربية',
			'lv'=>'Latviešu',
			'mk'=>'македонски',
			'uk'=>'Українська',
			'vi'=>'Tiếng Việt',
			'zh_TW'=>'正體中文（臺灣）',
			'af_ZA'=> 'Afrikaans',
			'bn_BD'=>'Bengali',
			'ta_LK'=>'தமிழ்',
			'zh_HK'=>'繁體中文（香港）',
			'is'=>'Icelandic',
			'ka_GE'=>'Georgian for Georgia',
			'ku_IQ'=>'Kurdish Iraq',
			'si_LK'=>'Sinhala',
			'be'=>'Belarusian',
			'ka'=>'Kartuli (Georgian)',
			'my_MM'=>'Burmese - MYANMAR ',
			'ur_PK'	=>'Urdu (Pakistan)',
		];
		$this->assertSame($expected, $languageArray);
	}
}
