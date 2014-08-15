<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 * @copyright 2013 Jakob Sack
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
class OC_L10N implements \OCP\IL10N {
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
	 * Plural forms (string)
	 */
	private $plural_form_string = 'nplurals=2; plural=(n != 1);';

	/**
	 * Plural forms (function)
	 */
	private $plural_form_function = null;

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
	 * @param string $transFile
	 */
	public function load($transFile) {
		$this->app = true;
		include $transFile;
		if(isset($TRANSLATIONS) && is_array($TRANSLATIONS)) {
			$this->translations = $TRANSLATIONS;
		}
		if(isset($PLURAL_FORMS)) {
			$this->plural_form_string = $PLURAL_FORMS;
		}
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
			$this->localizations = self::$cache[$app.'::'.$lang]['l'];
		}
		else{
			$i18ndir = self::findI18nDir($app);
			// Localization is in /l10n, Texts are in $i18ndir
			// (Just no need to define date/time format etc. twice)
			if((OC_Helper::isSubDirectory($i18ndir.$lang.'.php', OC::$SERVERROOT.'/core/l10n/')
				|| OC_Helper::isSubDirectory($i18ndir.$lang.'.php', OC::$SERVERROOT.'/lib/l10n/')
				|| OC_Helper::isSubDirectory($i18ndir.$lang.'.php', OC::$SERVERROOT.'/settings')
				|| OC_Helper::isSubDirectory($i18ndir.$lang.'.php', OC_App::getAppPath($app).'/l10n/')
				)
				&& file_exists($i18ndir.$lang.'.php')) {
				// Include the file, save the data from $CONFIG
				$transFile = strip_tags($i18ndir).strip_tags($lang).'.php';
				include $transFile;
				if(isset($TRANSLATIONS) && is_array($TRANSLATIONS)) {
					$this->translations = $TRANSLATIONS;
					//merge with translations from theme
					$theme = OC_Config::getValue( "theme" );
					if (!is_null($theme)) {
						$transFile = OC::$SERVERROOT.'/themes/'.$theme.substr($transFile, strlen(OC::$SERVERROOT));
						if (file_exists($transFile)) {
							include $transFile;
							if (isset($TRANSLATIONS) && is_array($TRANSLATIONS)) {
								$this->translations = array_merge($this->translations, $TRANSLATIONS);
							}
						}
					}
				}
				if(isset($PLURAL_FORMS)) {
					$this->plural_form_string = $PLURAL_FORMS;
				}
			}

			if(file_exists(OC::$SERVERROOT.'/core/l10n/l10n-'.$lang.'.php') && OC_Helper::isSubDirectory(OC::$SERVERROOT.'/core/l10n/l10n-'.$lang.'.php', OC::$SERVERROOT.'/core/l10n/')) {
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
	 * getPluralFormString
	 * @return string containing the gettext "Plural-Forms"-string
	 *
	 * Returns a string like "nplurals=2; plural=(n != 1);"
	 */
	public function getPluralFormString() {
		$this->init();
		return $this->plural_form_string;
	}

	/**
	 * getPluralFormFunction
	 * @return string the plural form function
	 *
	 * returned function accepts the argument $n
	 */
	public function getPluralFormFunction() {
		$this->init();
		if(is_null($this->plural_form_function)) {
			$this->plural_form_function = $this->createPluralFormFunction($this->plural_form_string);
		}
		return $this->plural_form_function;
	}

	/**
	 * get localizations
	 * @return array Fetch all localizations
	 *
	 * Returns an associative array with all localizations
	 */
	public function getLocalizations() {
		$this->init();
		return $this->localizations;
	}

	/**
	 * Localization
	 * @param string $type Type of localization
	 * @param array|int|string $data parameters for this localization
	 * @return String or false
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
				if($data instanceof DateTime) {
					$data = $data->getTimestamp();
				} elseif(is_string($data) && !is_numeric($data)) {
					$data = strtotime($data);
				}
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

		if(OC_User::getUser() && OC_Preferences::getValue(OC_User::getUser(), 'core', 'lang')) {
			$lang = OC_Preferences::getValue(OC_User::getUser(), 'core', 'lang');
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

		$default_language = OC_Config::getValue('default_language', false);

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
	 * @return directory
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
	 * find all available languages for an app
	 * @param string $app App that needs to be translated
	 * @return array an array of available languages
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

	/**
	 * @param string $app
	 * @param string $lang
	 * @return bool
	 */
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
