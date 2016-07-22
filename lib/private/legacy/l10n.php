<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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

/**
 * This class is for i18n and l10n
 * @deprecated 9.0.0 Use \OC::$server->getL10NFactory()->get() instead
 */
class OC_L10N implements \OCP\IL10N {
	/**
	 * cache
	 */
	protected static $cache = array();
	protected static $availableLanguages = array();

	/**
	 * The best language
	 */
	protected static $language = '';

	/**
	 * App of this object
	 */
	protected $app;

	/**
	 * Language of this object
	 */
	protected $lang;

	/**
	 * Translations
	 */
	private $translations = array();

	/**
	 * Plural forms (string)
	 */
	private $pluralFormString = 'nplurals=2; plural=(n != 1);';

	/**
	 * Plural forms (function)
	 */
	private $pluralFormFunction = null;

	/**
	 * The constructor
	 * @param string $app app requesting l10n
	 * @param string $lang default: null Language
	 *
	 * If language is not set, the constructor tries to find the right
	 * language.
	 * @deprecated 9.0.0 Use \OC::$server->getL10NFactory()->get() instead
	 */
	public function __construct($app, $lang = null) {
		$app = \OC_App::cleanAppId($app);
		$this->app = $app;

		if ($lang !== null) {
			$lang = str_replace(array('\0', '/', '\\', '..'), '', $lang);
		}

		// Find the right language
		if ($app !== 'test' && !\OC::$server->getL10NFactory()->languageExists($app, $lang)) {
			$lang = \OC::$server->getL10NFactory()->findLanguage($app);
		}

		$this->lang = $lang;
	}

	/**
	 * @param $transFile
	 * @return bool
	 */
	public function load($transFile) {
		$this->app = true;

		$json = json_decode(file_get_contents($transFile), true);
		if (!is_array($json)) {
			$jsonError = json_last_error();
			\OC::$server->getLogger()->warning("Failed to load $transFile - json error code: $jsonError", ['app' => 'l10n']);
			return false;
		}

		$this->pluralFormString = $json['pluralForm'];
		$translations = $json['translations'];

		$this->translations = array_merge($this->translations, $translations);

		return true;
	}

	protected function init() {
		if ($this->app === true) {
			return;
		}
		$app = $this->app;
		$lang = $this->lang;
		$this->app = true;

		/** @var \OC\L10N\Factory $factory */
		$factory = \OC::$server->getL10NFactory();
		$languageFiles = $factory->getL10nFilesForApp($app, $lang);

		$this->translations = [];
		foreach ($languageFiles as $languageFile) {
			$this->load($languageFile);
		}
	}

	/**
	 * Translating
	 * @param string $text The text we need a translation for
	 * @param array $parameters default:array() Parameters for sprintf
	 * @return \OC_L10N_String Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned.
	 */
	public function t($text, $parameters = array()) {
		return new OC_L10N_String($this, $text, $parameters);
	}

	/**
	 * Translating
	 * @param string $text_singular the string to translate for exactly one object
	 * @param string $text_plural the string to translate for n objects
	 * @param integer $count Number of objects
	 * @param array $parameters default:array() Parameters for sprintf
	 * @return \OC_L10N_String Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned. %n will be replaced with the number of objects.
	 *
	 * The correct plural is determined by the plural_forms-function
	 * provided by the po file.
	 *
	 */
	public function n($text_singular, $text_plural, $count, $parameters = array()) {
		$this->init();
		$identifier = "_${text_singular}_::_${text_plural}_";
		if( array_key_exists($identifier, $this->translations)) {
			return new OC_L10N_String( $this, $identifier, $parameters, $count );
		}else{
			if($count === 1) {
				return new OC_L10N_String($this, $text_singular, $parameters, $count);
			}else{
				return new OC_L10N_String($this, $text_plural, $parameters, $count);
			}
		}
	}

	/**
	 * getTranslations
	 * @return array Fetch all translations
	 *
	 * Returns an associative array with all translations
	 */
	public function getTranslations() {
		$this->init();
		return $this->translations;
	}

	/**
	 * getPluralFormFunction
	 * @return string the plural form function
	 *
	 * returned function accepts the argument $n
	 */
	public function getPluralFormFunction() {
		$this->init();
		if (is_null($this->pluralFormFunction)) {
			$this->pluralFormFunction = \OC::$server->getL10NFactory()->createPluralFunction($this->pluralFormString);
		}
		return $this->pluralFormFunction;
	}

	/**
	 * Localization
	 * @param string $type Type of localization
	 * @param array|int|string $data parameters for this localization
	 * @param array $options
	 * @return string|false
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
	public function l($type, $data, $options = array()) {
		if ($type === 'firstday') {
			return $this->getFirstWeekDay();
		}
		if ($type === 'jsdate') {
			return $this->getDateFormat();
		}

		$this->init();
		$value = new DateTime();
		if($data instanceof DateTime) {
			$value = $data;
		} elseif(is_string($data) && !is_numeric($data)) {
			$data = strtotime($data);
			$value->setTimestamp($data);
		} else {
			$value->setTimestamp($data);
		}

		// Use the language of the instance
		$locale = $this->transformToCLDRLocale($this->getLanguageCode());

		$options = array_merge(array('width' => 'long'), $options);
		$width = $options['width'];
		switch($type) {
			case 'date':
				return Punic\Calendar::formatDate($value, $width, $locale);
			case 'datetime':
				return Punic\Calendar::formatDatetime($value, $width, $locale);
			case 'time':
				return Punic\Calendar::formatTime($value, $width, $locale);
			default:
				return false;
		}
	}

	/**
	 * The code (en, de, ...) of the language that is used for this OC_L10N object
	 *
	 * @return string language
	 */
	public function getLanguageCode() {
		return $this->lang;
	}

	/**
	 * @return string
	 * @throws \Punic\Exception\ValueNotInList
	 * @deprecated 9.0.0 Use $this->l('jsdate', null) instead
	 */
	public function getDateFormat() {
		$locale = $this->transformToCLDRLocale($this->getLanguageCode());
		return Punic\Calendar::getDateFormat('short', $locale);
	}

	/**
	 * @return int
	 * @deprecated 9.0.0 Use $this->l('firstday', null) instead
	 */
	public function getFirstWeekDay() {
		$locale = $this->transformToCLDRLocale($this->getLanguageCode());
		return Punic\Calendar::getFirstWeekday($locale);
	}

	/**
	 * @param string $locale
	 * @return string
	 */
	private function transformToCLDRLocale($locale) {
		if ($locale === 'sr@latin') {
			return 'sr_latn';
		}

		return $locale;
	}

	/**
	 * find the best language
	 * @param string $app
	 * @return string language
	 *
	 * If nothing works it returns 'en'
	 * @deprecated 9.0.0 Use \OC::$server->getL10NFactory()->findLanguage() instead
	 */
	public static function findLanguage($app = null) {
		return \OC::$server->getL10NFactory()->findLanguage($app);
	}

	/**
	 * @return string
	 * @deprecated 9.0.0 Use \OC::$server->getL10NFactory()->setLanguageFromRequest() instead
	 */
	public static function setLanguageFromRequest() {
		return \OC::$server->getL10NFactory()->setLanguageFromRequest();
	}

	/**
	 * find all available languages for an app
	 * @param string $app App that needs to be translated
	 * @return array an array of available languages
	 * @deprecated 9.0.0 Use \OC::$server->getL10NFactory()->findAvailableLanguages() instead
	 */
	public static function findAvailableLanguages($app=null) {
		return \OC::$server->getL10NFactory()->findAvailableLanguages($app);
	}

	/**
	 * @param string $app
	 * @param string $lang
	 * @return bool
	 * @deprecated 9.0.0 Use \OC::$server->getL10NFactory()->languageExists() instead
	 */
	public static function languageExists($app, $lang) {
		return \OC::$server->getL10NFactory()->languageExists($app, $lang);
	}
}
