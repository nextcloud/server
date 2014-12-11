<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 * @copyright 2013 Jakob Sack
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class is for i18n and l10n
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
	 * get an L10N instance
	 * @param string $app
	 * @param string|null $lang
	 * @return \OC_L10N
	 */
	public static function get($app, $lang=null) {
		if (is_null($lang)) {
			return OC::$server->getL10N($app);
		} else {
			return new \OC_L10N($app, $lang);
		}
	}

	/**
	 * The constructor
	 * @param string $app app requesting l10n
	 * @param string $lang default: null Language
	 *
	 * If language is not set, the constructor tries to find the right
	 * language.
	 */
	public function __construct($app, $lang = null) {
		$this->app = $app;
		$this->lang = $lang;
	}

	/**
	 * @param $transFile
	 * @param bool $mergeTranslations
	 * @return bool
	 */
	public function load($transFile, $mergeTranslations = false) {
		$this->app = true;

		$json = json_decode(file_get_contents($transFile), true);
		if (!is_array($json)) {
			return false;
		}

		$this->pluralFormString = $json['pluralForm'];
		$translations = $json['translations'];

		if ($mergeTranslations) {
			$this->translations = array_merge($this->translations, $translations);
		} else {
			$this->translations = $translations;
		}

		return true;
	}

	protected function init() {
		if ($this->app === true) {
			return;
		}
		$app = OC_App::cleanAppId($this->app);
		$lang = str_replace(array('\0', '/', '\\', '..'), '', $this->lang);
		$this->app = true;
		// Find the right language
		if(is_null($lang) || $lang == '') {
			$lang = self::findLanguage($app);
		}

		// Use cache if possible
		if(array_key_exists($app.'::'.$lang, self::$cache)) {
			$this->translations = self::$cache[$app.'::'.$lang]['t'];
		} else{
			$i18nDir = self::findI18nDir($app);
			$transFile = strip_tags($i18nDir).strip_tags($lang).'.json';
			// Texts are in $i18ndir
			// (Just no need to define date/time format etc. twice)
			if((OC_Helper::isSubDirectory($transFile, OC::$SERVERROOT.'/core/l10n/')
				|| OC_Helper::isSubDirectory($transFile, OC::$SERVERROOT.'/lib/l10n/')
				|| OC_Helper::isSubDirectory($transFile, OC::$SERVERROOT.'/settings')
				|| OC_Helper::isSubDirectory($transFile, OC_App::getAppPath($app).'/l10n/')
				)
				&& file_exists($transFile)) {
				// load the translations file
				if($this->load($transFile)) {
					//merge with translations from theme
					$theme = \OC::$server->getConfig()->getSystemValue('theme');
					if (!empty($theme)) {
						$transFile = OC::$SERVERROOT.'/themes/'.$theme.substr($transFile, strlen(OC::$SERVERROOT));
						if (file_exists($transFile)) {
							$this->load($transFile, true);
						}
					}
				}
			}

			self::$cache[$app.'::'.$lang]['t'] = $this->translations;
		}
	}

	/**
	 * Creates a function that The constructor
	 *
	 * If language is not set, the constructor tries to find the right
	 * language.
	 *
	 * Parts of the code is copied from Habari:
	 * https://github.com/habari/system/blob/master/classes/locale.php
	 * @param string $string
	 * @return string
	 */
	protected function createPluralFormFunction($string){
		if(preg_match( '/^\s*nplurals\s*=\s*(\d+)\s*;\s*plural=(.*)$/u', $string, $matches)) {
			// sanitize
			$nplurals = preg_replace( '/[^0-9]/', '', $matches[1] );
			$plural = preg_replace( '#[^n0-9:\(\)\?\|\&=!<>+*/\%-]#', '', $matches[2] );

			$body = str_replace(
				array( 'plural', 'n', '$n$plurals', ),
				array( '$plural', '$n', '$nplurals', ),
				'nplurals='. $nplurals . '; plural=' . $plural
			);

			// add parents
			// important since PHP's ternary evaluates from left to right
			$body .= ';';
			$res = '';
			$p = 0;
			for($i = 0; $i < strlen($body); $i++) {
				$ch = $body[$i];
				switch ( $ch ) {
				case '?':
					$res .= ' ? (';
					$p++;
					break;
				case ':':
					$res .= ') : (';
					break;
				case ';':
					$res .= str_repeat( ')', $p ) . ';';
					$p = 0;
					break;
				default:
					$res .= $ch;
				}
			}

			$body = $res . 'return ($plural>=$nplurals?$nplurals-1:$plural);';
			return create_function('$n', $body);
		}
		else {
			// default: one plural form for all cases but n==1 (english)
			return create_function(
				'$n',
				'$nplurals=2;$plural=($n==1?0:1);return ($plural>=$nplurals?$nplurals-1:$plural);'
			);
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
			return new OC_L10N_String($this, $identifier, $parameters, $count, array($text_singular, $text_plural));
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
		if(is_null($this->pluralFormFunction)) {
			$this->pluralFormFunction = $this->createPluralFormFunction($this->pluralFormString);
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

		// Use the language of the instance, before falling back to the current user's language
		$locale = $this->lang;
		if ($locale === null) {
			$locale = self::findLanguage();
		}

		$options = array_merge(array('width' => 'long'), $options);
		$width = $options['width'];
		switch($type) {
			case 'date':
				return Punic\Calendar::formatDate($value, $width, $locale);
				break;
			case 'datetime':
				return Punic\Calendar::formatDatetime($value, $width, $locale);
				break;
			case 'time':
				return Punic\Calendar::formatTime($value, $width, $locale);
				break;
			default:
				return false;
		}
	}

	/**
	 * Choose a language
	 * @param array $text Associative Array with possible strings
	 * @return String
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
	 * The given language is forced to be used while executing the current request
	 * @param string $lang
	 */
	public static function forceLanguage($lang) {
		self::$language = $lang;
	}


	/**
	 * find the best language
	 *
	 * @param array|string $app details below
	 *
	 * If $app is an array, ownCloud assumes that these are the available
	 * languages. Otherwise ownCloud tries to find the files in the l10n
	 * folder.
	 *
	 * If nothing works it returns 'en'
	 * @return string language
	 */
	public function getLanguageCode($app=null) {
		return self::findLanguage($app);
	}


	/**
	 * find the best language
	 * @param array|string $app details below
	 * @return string language
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

		if(OC_User::getUser() && \OC::$server->getConfig()->getUserValue(OC_User::getUser(), 'core', 'lang')) {
			$lang = \OC::$server->getConfig()->getUserValue(OC_User::getUser(), 'core', 'lang');
			self::$language = $lang;
			if(is_array($app)) {
				$available = $app;
				$lang_exists = array_search($lang, $available) !== false;
			} else {
				$lang_exists = self::languageExists($app, $lang);
			}
			if($lang_exists) {
				return $lang;
			}
		}

		$default_language = \OC::$server->getConfig()->getSystemValue('default_language', false);

		if($default_language !== false) {
			return $default_language;
		}

		if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			if(is_array($app)) {
				$available = $app;
			} else {
				$available = self::findAvailableLanguages($app);
			}

			// E.g. make sure that 'de' is before 'de_DE'.
			sort($available);

			$preferences = preg_split('/,\s*/', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
			foreach($preferences as $preference) {
				list($preferred_language) = explode(';', $preference);
				$preferred_language = str_replace('-', '_', $preferred_language);
				foreach($available as $available_language) {
					if ($preferred_language === strtolower($available_language)) {
						if (is_null($app)) {
							self::$language = $available_language;
						}
						return $available_language;
					}
				}
				foreach($available as $available_language) {
					if (substr($preferred_language, 0, 2) === $available_language) {
						if (is_null($app)) {
							self::$language = $available_language;
						}
						return $available_language;
					}
				}
			}
		}

		// Last try: English
		return 'en';
	}

	/**
	 * find the l10n directory
	 * @param string $app App that needs to be translated
	 * @return string directory
	 */
	protected static function findI18nDir($app) {
		// find the i18n dir
		$i18nDir = OC::$SERVERROOT.'/core/l10n/';
		if($app != '') {
			// Check if the app is in the app folder
			if(file_exists(OC_App::getAppPath($app).'/l10n/')) {
				$i18nDir = OC_App::getAppPath($app).'/l10n/';
			}
			else{
				$i18nDir = OC::$SERVERROOT.'/'.$app.'/l10n/';
			}
		}
		return $i18nDir;
	}

	/**
	 * find all available languages for an app
	 * @param string $app App that needs to be translated
	 * @return array an array of available languages
	 */
	public static function findAvailableLanguages($app=null) {
		if(!empty(self::$availableLanguages)) {
			return self::$availableLanguages;
		}
		$available=array('en');//english is always available
		$dir = self::findI18nDir($app);
		if(is_dir($dir)) {
			$files=scandir($dir);
			foreach($files as $file) {
				if(substr($file, -5, 5) === '.json' && substr($file, 0, 4) !== 'l10n') {
					$i = substr($file, 0, -5);
					$available[] = $i;
				}
			}
		}

		self::$availableLanguages = $available;
		return $available;
	}

	/**
	 * @param string $app
	 * @param string $lang
	 * @return bool
	 */
	public static function languageExists($app, $lang) {
		if ($lang === 'en') {//english is always available
			return true;
		}
		$dir = self::findI18nDir($app);
		if(is_dir($dir)) {
			return file_exists($dir.'/'.$lang.'.json');
		}
		return false;
	}

	/**
	 * @return string
	 * @throws \Punic\Exception\ValueNotInList
	 */
	public function getDateFormat() {
		$locale = self::findLanguage();
		return Punic\Calendar::getDateFormat('short', $locale);
	}

	/**
	 * @return int
	 */
	public function getFirstWeekDay() {
		$locale = self::findLanguage();
		return Punic\Calendar::getFirstWeekday($locale);
	}
}
