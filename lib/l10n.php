<?php
/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class is for i18n and l10n
 */
class OC_L10N{
	/**
	 * cached instances
	 */
	protected static $instances=array();

	/**
	 * cache
	 */
	protected static $cache = array();

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
	 * Localization
	 */
	private $localizations = array(
		'jsdate' => 'dd.mm.yy',
		'date' => '%d.%m.%Y',
		'datetime' => '%d.%m.%Y %H:%M:%S',
		'time' => '%H:%M:%S',
		'firstday' => 0);

	/**
	 * get an L10N instance
	 * @return OC_L10N
	 */
	public static function get($app, $lang=null) {
		if(is_null($lang)) {
			if(!isset(self::$instances[$app])) {
				self::$instances[$app]=new OC_L10N($app);
			}
			return self::$instances[$app];
		}else{
			return new OC_L10N($app, $lang);
		}
	}

	/**
	 * @brief The constructor
	 * @param $app the app requesting l10n
	 * @param $lang default: null Language
	 * @returns OC_L10N-Object
	 *
	 * If language is not set, the constructor tries to find the right
	 * language.
	 */
	public function __construct($app, $lang = null) {
		$this->app = $app;
		$this->lang = $lang;
	}

	protected function init() {
		if ($this->app === true) {
			return;
		}
		$app = OC_App::cleanAppId($this->app);
		$lang = $this->lang;
		$this->app = true;
		// Find the right language
		if(is_null($lang) || $lang == '') {
			$lang = self::findLanguage($app);
		}

		// Use cache if possible
		if(array_key_exists($app.'::'.$lang, self::$cache)) {

			$this->translations = self::$cache[$app.'::'.$lang]['t'];
			$this->localizations = self::$cache[$app.'::'.$lang]['l'];
		}
		else{
			$i18ndir = self::findI18nDir($app);
			// Localization is in /l10n, Texts are in $i18ndir
			// (Just no need to define date/time format etc. twice)
			if((OC_Helper::issubdirectory($i18ndir.$lang.'.php', OC_App::getAppPath($app).'/l10n/')
				|| OC_Helper::issubdirectory($i18ndir.$lang.'.php', OC::$SERVERROOT.'/core/l10n/')
				|| OC_Helper::issubdirectory($i18ndir.$lang.'.php', OC::$SERVERROOT.'/lib/l10n/')
				|| OC_Helper::issubdirectory($i18ndir.$lang.'.php', OC::$SERVERROOT.'/settings')
				)
				&& file_exists($i18ndir.$lang.'.php')) {
				// Include the file, save the data from $CONFIG
				include strip_tags($i18ndir).strip_tags($lang).'.php';
				if(isset($TRANSLATIONS) && is_array($TRANSLATIONS)) {
					$this->translations = $TRANSLATIONS;
				}
			}

			if(file_exists(OC::$SERVERROOT.'/core/l10n/l10n-'.$lang.'.php')) {
				// Include the file, save the data from $CONFIG
				include OC::$SERVERROOT.'/core/l10n/l10n-'.$lang.'.php';
				if(isset($LOCALIZATIONS) && is_array($LOCALIZATIONS)) {
					$this->localizations = array_merge($this->localizations, $LOCALIZATIONS);
				}
			}

			self::$cache[$app.'::'.$lang]['t'] = $this->translations;
			self::$cache[$app.'::'.$lang]['l'] = $this->localizations;
		}
	}

	/**
	 * @brief Translating
	 * @param $text String The text we need a translation for
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
	 * @brief Translating
	 * @param $textArray The text array we need a translation for
	 * @returns Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $textArray will be
	 * returned.
	 *
	 *
	 * @deprecated deprecated since ownCloud version 5.0
	 * This method will probably be removed with ownCloud 6.0
	 *
	 *
	 */
	public function tA($textArray) {
		OC_Log::write('core', 'DEPRECATED: the method tA is deprecated and will be removed soon.', OC_Log::WARN);
		$result = array();
		foreach($textArray as $key => $text) {
			$result[$key] = (string)$this->t($text);
		}
		return $result;
	}

	/**
	 * @brief getTranslations
	 * @returns Fetch all translations
	 *
	 * Returns an associative array with all translations
	 */
	public function getTranslations() {
		$this->init();
		return $this->translations;
	}

	/**
	 * @brief Localization
	 * @param $type Type of localization
	 * @param $params parameters for this localization
	 * @returns String or false
	 *
	 * Returns the localized data.
	 *
	 * Implemented types:
	 *  - date
	 *    - Creates a date
	 *    - l10n-field: date
	 *    - params: timestamp (int/string)
	 *  - datetime
	 *    - Creates date and time
	 *    - l10n-field: datetime
	 *    - params: timestamp (int/string)
	 *  - time
	 *    - Creates a time
	 *    - l10n-field: time
	 *    - params: timestamp (int/string)
	 */
	public function l($type, $data) {
		$this->init();
		switch($type) {
			// If you add something don't forget to add it to $localizations
			// at the top of the page
			case 'date':
			case 'datetime':
			case 'time':
				if($data instanceof DateTime) return $data->format($this->localizations[$type]);
				elseif(is_string($data)) $data = strtotime($data);
				$locales = array(self::findLanguage());
				if (strlen($locales[0]) == 2) {
					$locales[] = $locales[0].'_'.strtoupper($locales[0]);
				}
				setlocale(LC_TIME, $locales);
				$format = $this->localizations[$type];
				// Check for Windows to find and replace the %e modifier correctly
				if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
					$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
				}
				return strftime($format, $data);
				break;
			case 'firstday':
			case 'jsdate':
				return $this->localizations[$type];
			default:
				return false;
		}
	}

	/**
	 * @brief Choose a language
	 * @param $texts Associative Array with possible strings
	 * @returns String
	 *
	 * $text is an array 'de' => 'hallo welt', 'en' => 'hello world', ...
	 *
	 * This function is useful to avoid loading thousands of files if only one
	 * simple string is needed, for example in appinfo.php
	 */
	public static function selectLanguage($text) {
		$lang = self::findLanguage(array_keys($text));
		return $text[$lang];
	}

	/**
	 * @brief find the best language
	 * @param $app Array or string, details below
	 * @returns language
	 *
	 * If $app is an array, ownCloud assumes that these are the available
	 * languages. Otherwise ownCloud tries to find the files in the l10n
	 * folder.
	 *
	 * If nothing works it returns 'en'
	 */
	public static function findLanguage($app = null) {
		if(!is_array($app) && self::$language != '') {
			return self::$language;
		}

		if(OC_User::getUser() && OC_Preferences::getValue(OC_User::getUser(), 'core', 'lang')) {
			$lang = OC_Preferences::getValue(OC_User::getUser(), 'core', 'lang');
			self::$language = $lang;
			if(is_array($app)) {
				$available = $app;
				$lang_exists = array_search($lang, $available) !== false;
			}
			else {
				$lang_exists = self::languageExists($app, $lang);
			}
			if($lang_exists) {
				return $lang;
			}
		}

		if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$accepted_languages = preg_split('/,\s*/', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
			if(is_array($app)) {
				$available = $app;
			}
			else{
				$available = self::findAvailableLanguages($app);
			}
			foreach($accepted_languages as $i) {
				$temp = explode(';', $i);
				$temp[0] = str_replace('-', '_', $temp[0]);
				if( ($key = array_search($temp[0], $available)) !== false) {
					return $available[$key];
				}
				foreach($available as $l) {
					if ( $temp[0] == substr($l, 0, 2) ) {
						return $l;
					}
				}
			}
		}

		// Last try: English
		return 'en';
	}

	/**
	 * @brief find the l10n directory
	 * @param $app App that needs to be translated
	 * @returns directory
	 */
	protected static function findI18nDir($app) {
		// find the i18n dir
		$i18ndir = OC::$SERVERROOT.'/core/l10n/';
		if($app != '') {
			// Check if the app is in the app folder
			if(file_exists(OC_App::getAppPath($app).'/l10n/')) {
				$i18ndir = OC_App::getAppPath($app).'/l10n/';
			}
			else{
				$i18ndir = OC::$SERVERROOT.'/'.$app.'/l10n/';
			}
		}
		return $i18ndir;
	}

	/**
	 * @brief find all available languages for an app
	 * @param $app App that needs to be translated
	 * @returns array an array of available languages
	 */
	public static function findAvailableLanguages($app=null) {
		$available=array('en');//english is always available
		$dir = self::findI18nDir($app);
		if(is_dir($dir)) {
			$files=scandir($dir);
			foreach($files as $file) {
				if(substr($file, -4, 4) === '.php' && substr($file, 0, 4) !== 'l10n') {
					$i = substr($file, 0, -4);
					$available[] = $i;
				}
			}
		}
		return $available;
	}

	public static function languageExists($app, $lang) {
		if ($lang == 'en') {//english is always available
			return true;
		}
		$dir = self::findI18nDir($app);
		if(is_dir($dir)) {
			return file_exists($dir.'/'.$lang.'.php');
		}
		return false;
	}
}
