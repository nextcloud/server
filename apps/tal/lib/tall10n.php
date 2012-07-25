<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

//require_once 'PHPTAL/TranslationService.php';


class OC_TALL10N extends OC_L10N implements PHPTAL_TranslationService {
	private $encoding = 'UTF-8';
    private $vars = array();
	//private $lang = '';
	//private $app = '';

	/**
	 * @brief The constructor
	 * @param $app the app requesting l10n
	 * @param $lang default: null Language
	 * @returns OC_L10N-Object
	 *
	 * If language is not set, the constructor tries to find the right
	 * language.
	 */
	public function __construct($app, $lang = null){
		//$this->app = $app;
		//$this->lang = $lang;
		parent::__construct($app, $lang);
	}

	/**
	* Set the target language for translations.
	* @return string - chosen language
	*/
	function setLanguage(/*...*/) {
		$langs = func_get_args();
		$this->language = $langs[0];
	}

	/**
	* PHPTAL will inform translation service what encoding page uses.
	* Output of translate() must be in this encoding.
	* NOTE: Currently not used (and probably won't be as we use utf-8 all over?).
	*/
	function setEncoding($encoding) {
		$this->encoding = $encoding;
	}

	/**
	* Set the domain to use for translations (if different parts of application are translated in different files. This is not for language selection).
	*/
    function useDomain($domain) {
		if(!$domain) {
			return;
		}
		error_log('useDomain: '.$domain);
		$this->app = $domain;
		$this->init();
	}

	/**
	* Set value of a variable used in translation key.
	*
	* You should use it to replace all {key}s with values in translated strings.
	*
	* @param string $key - name of the variable
	* @param string $value
	*/
    public function setVar($key, $value) {
		error_log('setVar: '.$key.'=>'.$value);
        $this->vars[$key] = $value;
    }

	/**
	* Translate a gettext key and interpolate variables.
	*
	* @param string $key - translation key, e.g. "hello {username}!"
	* @param string $htmlescape - if true, you should HTML-escape translated string. You should never HTML-escape interpolated variables.
	*/
	function translate($key, $escape=true) {
		$translations = $this->getTranslations();
		if (array_key_exists($key, $translations)) {
			$v = $translations[$key];
		} else {
			$v = $key;
		}

		if ($escape) {
			$v = htmlspecialchars($v);
		}

		//while (preg_match('/\{(.*?)\}/sm', $v, $m)) {
        while (preg_match('/\$\{(.*?)\}/sm', $v, $m)) {
			list($src, $var) = $m;
			if (!isset($this->vars[$var])) {
				$v = str_replace($src, 'undefined', $v);
			} else {
				$v = str_replace($src, $this->vars[$var], $v);
			}
		}
		return $v;
	}
}