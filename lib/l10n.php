<?php
/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
	 * cache
	 */
	protected static $cache = array();
	
	/**
	 * The best language
	 */
	protected static $language = '';
	
	/**
	 * Translations
	 */
	private $translations = array();
	
	/**
	 * Localization
	 */
	private $localizations = array(
		'date' => 'd.m.Y',
		'datetime' => 'd.m.Y H:i:s',
		'time' => 'H:i:s' );
	
	/**
	 * @brief The constructor
	 * @param $app the app requesting l10n
	 * @param $lang default: null Language
	 * @returns OC_L10N-Object
	 *
	 * If language is not set, the constructor tries to find the right
	 * language.
	 */
	public function __construct( $app, $lang = null ){
		// Find the right language
		if( is_null( $lang )){
			self::findLanguage( $app );
		}

		// Use cache if possible
		if(array_key_exists($app.'::'.$lang, self::$cache )){
			$this->translations = self::$cache[$app.'::'.$lang]['t'];
			$this->localizations = self::$cache[$app.'::'.$lang]['l'];
		}
		else{
			$i18ndir = self::findI18nDir( $app );

			// Localization is in /l10n, Texts are in $i18ndir
			// (Just no need to define date/time format etc. twice)
			if( file_exists( $i18ndir.$lang.'php' )){
				// Include the file, save the data from $CONFIG
				include( $i18ndir.$lang.'php' );
				if( isset( $TRANSLATIONS ) && is_array( $TRANSLATIONS )){
					$this->translations = $TRANSLATIONS;
				}
			}

			if( file_exists( '/l10n/l10n-'.$lang.'php' )){
				// Include the file, save the data from $CONFIG
				include( $SERVERROOT.'/l10n/l10n-'.$lang.'php' );
				if( isset( $LOCALIZATIONS ) && is_array( $LOCALIZATIONS )){
					$this->localizations = array_merge( $this->localizations, $LOCALIZATIONS );
				}
			}

			self::$cache[$app.'::'.$lang]['t'] = $this->translations;
			self::$cache[$app.'::'.$lang]['l'] = $this->localizations;
		}
	}

	/**
	 * @brief Translating
	 * @param $text The text we need a translation for
	 * @returns Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned.
	 */
	public function t($text){
		if(array_key_exists($text, $this->translations)){
			return $this->translations[$text];
		}
		return $text;
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
	 *    - params: timestamp (int)
	 *  - datetime
	 *    - Creates date and time
	 *    - l10n-field: datetime
	 *    - params: timestamp (int)
	 *  - time
	 *    - Creates a time
	 *    - l10n-field: time
	 *    - params: timestamp (int)
	 */
	public function l($type, $data){
		switch($type){
			case 'date':
				return date( $this->localizations['date'], $data );
				break;
			case 'datetime':
				return date( $this->localizations['datetime'], $data );
				break;
			case 'time':
				return date( $this->localizations['time'], $data );
				break;
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
	public static function selectLanguage( $text ){
		$lang = self::findLanguage( array_keys( $text ));
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
	public static function findLanguage( $app = null ){
		if( !is_array( $app) && self::$language != '' ){
			return self::$language;
		}

		$available = array();
		if( is_array( $app )){
			$available = $app;
		}
		else{
			$dir = self::findI18nDir( $app );
			if( file_exists($dir)){
				$dh = opendir($dir);
				while(( $file = readdir( $dh )) !== false ){
					if( substr( $file, -4, 4 ) == '.php' ){
						$i = substr( $file, 0, -4 );
						if( $i != '' ){
							$available[] = $i;
						}
					}
				}
				closedir($dh);
			}
		}

		if( isset($_SESSION['user_id']) && OC_PREFERENCES::getValue( $_SESSION['user_id'], 'core', 'lang' )){
			$lang = OC_PREFERENCES::getValue( $_SESSION['user_id'], 'core', 'lang' );
			self::$language = $lang;
			if( array_search( $lang, $available )){
				return $lang;
			}
		}

		if( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] )){
			$accepted_languages = preg_split( '/,\s*/', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			foreach( $accepted_languages as $i ){
				$temp = explode( ';', $i );
				$temp = explode( '-', $temp[0] );
				if( array_key_exists( $temp[0], $available )){
					return $temp[0];
				}
			}
		}

		// Last try: English
		return 'en';
	}

	/**
	 * @brief find the best language
	 * @param $app App that needs to be translated
	 * @returns language
	 *
	 * Finds the best language. Depends on user settings and browser
	 * information
	 */
	protected static function findI18nDir( $app ){
		global $SERVERROOT;
		
		// find the i18n dir
		$i18ndir = $SERVERROOT.'/l10n/';
		if( $app != 'core' && $app != '' ){
			// Check if the app is in the app folder
			if( file_exists( $SERVERROOT.'/apps/'.$app.'/l10n/' )){
				$i18ndir = $SERVERROOT.'/apps/'.$app.'/l10n/';
			}
			else{
				$i18ndir = $SERVERROOT.'/'.$app.'/l10n/';
			}
		}
		return $i18ndir;
	}
}