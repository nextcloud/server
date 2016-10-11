<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\L10N;

use OCP\IL10N;
use OCP\L10N\IFactory;
use Punic\Calendar;

class L10N implements IL10N {

	/** @var IFactory */
	protected $factory;

	/** @var string App of this object */
	protected $app;

	/** @var string Language of this object */
	protected $lang;

	/** @var string Plural forms (string) */
	private $pluralFormString = 'nplurals=2; plural=(n != 1);';

	/** @var string Plural forms (function) */
	private $pluralFormFunction = null;

	/** @var string[] */
	private $translations = [];

	/**
	 * @param IFactory $factory
	 * @param string $app
	 * @param string $lang
	 * @param array $files
	 */
	public function __construct(IFactory $factory, $app, $lang, array $files) {
		$this->factory = $factory;
		$this->app = $app;
		$this->lang = $lang;

		$this->translations = [];
		foreach ($files as $languageFile) {
			$this->load($languageFile);
		}
	}

	/**
	 * The code (en, de, ...) of the language that is used for this instance
	 *
	 * @return string language
	 */
	public function getLanguageCode() {
		return $this->lang;
	}

	/**
	 * Translating
	 * @param string $text The text we need a translation for
	 * @param array $parameters default:array() Parameters for sprintf
	 * @return string Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned.
	 */
	public function t($text, $parameters = array()) {
		return (string) new \OC_L10N_String($this, $text, $parameters);
	}

	/**
	 * Translating
	 * @param string $text_singular the string to translate for exactly one object
	 * @param string $text_plural the string to translate for n objects
	 * @param integer $count Number of objects
	 * @param array $parameters default:array() Parameters for sprintf
	 * @return string Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned. %n will be replaced with the number of objects.
	 *
	 * The correct plural is determined by the plural_forms-function
	 * provided by the po file.
	 *
	 */
	public function n($text_singular, $text_plural, $count, $parameters = array()) {
		$identifier = "_${text_singular}_::_${text_plural}_";
		if (isset($this->translations[$identifier])) {
			return (string) new \OC_L10N_String($this, $identifier, $parameters, $count);
		} else {
			if ($count === 1) {
				return (string) new \OC_L10N_String($this, $text_singular, $parameters, $count);
			} else {
				return (string) new \OC_L10N_String($this, $text_plural, $parameters, $count);
			}
		}
	}

	/**
	 * Localization
	 * @param string $type Type of localization
	 * @param \DateTime|int|string $data parameters for this localization
	 * @param array $options
	 * @return string|int|false
	 *
	 * Returns the localized data.
	 *
	 * Implemented types:
	 *  - date
	 *    - Creates a date
	 *    - params: timestamp (int/string)
	 *  - datetime
	 *    - Creates date and time
	 *    - params: timestamp (int/string)
	 *  - time
	 *    - Creates a time
	 *    - params: timestamp (int/string)
	 *  - firstday: Returns the first day of the week (0 sunday - 6 saturday)
	 *  - jsdate: Returns the short JS date format
	 */
	public function l($type, $data = null, $options = array()) {
		// Use the language of the instance
		$locale = $this->getLanguageCode();
		if ($locale === 'sr@latin') {
			$locale = 'sr_latn';
		}

		if ($type === 'firstday') {
			return (int) Calendar::getFirstWeekday($locale);
		}
		if ($type === 'jsdate') {
			return (string) Calendar::getDateFormat('short', $locale);
		}

		$value = new \DateTime();
		if ($data instanceof \DateTime) {
			$value = $data;
		} else if (is_string($data) && !is_numeric($data)) {
			$data = strtotime($data);
			$value->setTimestamp($data);
		} else if ($data !== null) {
			$value->setTimestamp($data);
		}

		$options = array_merge(array('width' => 'long'), $options);
		$width = $options['width'];
		switch ($type) {
			case 'date':
				return (string) Calendar::formatDate($value, $width, $locale);
			case 'datetime':
				return (string) Calendar::formatDatetime($value, $width, $locale);
			case 'time':
				return (string) Calendar::formatTime($value, $width, $locale);
			default:
				return false;
		}
	}

	/**
	 * Returns an associative array with all translations
	 *
	 * Called by \OC_L10N_String
	 * @return array
	 */
	public function getTranslations() {
		return $this->translations;
	}

	/**
	 * Returnsed function accepts the argument $n
	 *
	 * Called by \OC_L10N_String
	 * @return string the plural form function
	 */
	public function getPluralFormFunction() {
		if (is_null($this->pluralFormFunction)) {
			$this->pluralFormFunction = $this->factory->createPluralFunction($this->pluralFormString);
		}
		return $this->pluralFormFunction;
	}

	/**
	 * @param $translationFile
	 * @return bool
	 */
	protected function load($translationFile) {
		$json = json_decode(file_get_contents($translationFile), true);
		if (!is_array($json)) {
			$jsonError = json_last_error();
			\OC::$server->getLogger()->warning("Failed to load $translationFile - json error code: $jsonError", ['app' => 'l10n']);
			return false;
		}

		if (!empty($json['pluralForm'])) {
			$this->pluralFormString = $json['pluralForm'];
		}
		$this->translations = array_merge($this->translations, $json['translations']);
		return true;
	}
}
