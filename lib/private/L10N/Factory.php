<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;

/**
 * A factory that generates language instances
 */
class Factory implements IFactory {

	/** @var string */
	protected $requestLanguage = '';

	/**
	 * cached instances
	 * @var array Structure: Lang => App => \OCP\IL10N
	 */
	protected $instances = [];

	/**
	 * @var array Structure: App => string[]
	 */
	protected $availableLanguages = [];

	/**
	 * @var array Structure: string => callable
	 */
	protected $pluralFunctions = [];

	/** @var IConfig */
	protected $config;

	/** @var IRequest */
	protected $request;

	/** @var IUserSession */
	protected $userSession;

	/** @var string */
	protected $serverRoot;

	/**
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param string $serverRoot
	 */
	public function __construct(IConfig $config,
								IRequest $request,
								IUserSession $userSession,
								$serverRoot) {
		$this->config = $config;
		$this->request = $request;
		$this->userSession = $userSession;
		$this->serverRoot = $serverRoot;
	}

	/**
	 * Get a language instance
	 *
	 * @param string $app
	 * @param string|null $lang
	 * @return \OCP\IL10N
	 */
	public function get($app, $lang = null) {
		$app = \OC_App::cleanAppId($app);
		if ($lang !== null) {
			$lang = str_replace(array('\0', '/', '\\', '..'), '', (string) $lang);
		}
		if ($lang === null || !$this->languageExists($app, $lang)) {
			$lang = $this->findLanguage($app);
		}

		if (!isset($this->instances[$lang][$app])) {
			$this->instances[$lang][$app] = new L10N(
				$this, $app, $lang,
				$this->getL10nFilesForApp($app, $lang)
			);
		}

		return $this->instances[$lang][$app];
	}

	/**
	 * Find the best language
	 *
	 * @param string|null $app App id or null for core
	 * @return string language If nothing works it returns 'en'
	 */
	public function findLanguage($app = null) {
		if ($this->requestLanguage !== '' && $this->languageExists($app, $this->requestLanguage)) {
			return $this->requestLanguage;
		}

		/**
		 * At this point ownCloud might not yet be installed and thus the lookup
		 * in the preferences table might fail. For this reason we need to check
		 * whether the instance has already been installed
		 *
		 * @link https://github.com/owncloud/core/issues/21955
		 */
		if($this->config->getSystemValue('installed', false)) {
			$userId = !is_null($this->userSession->getUser()) ? $this->userSession->getUser()->getUID() :  null;
			if(!is_null($userId)) {
				$userLang = $this->config->getUserValue($userId, 'core', 'lang', null);
			} else {
				$userLang = null;
			}
		} else {
			$userId = null;
			$userLang = null;
		}

		if ($userLang) {
			$this->requestLanguage = $userLang;
			if ($this->languageExists($app, $userLang)) {
				return $userLang;
			}
		}

		$defaultLanguage = $this->config->getSystemValue('default_language', false);

		if ($defaultLanguage !== false && $this->languageExists($app, $defaultLanguage)) {
			return $defaultLanguage;
		}

		$lang = $this->setLanguageFromRequest($app);
		if ($userId !== null && $app === null && !$userLang) {
			$this->config->setUserValue($userId, 'core', 'lang', $lang);
		}

		return $lang;
	}

	/**
	 * Find all available languages for an app
	 *
	 * @param string|null $app App id or null for core
	 * @return array an array of available languages
	 */
	public function findAvailableLanguages($app = null) {
		$key = $app;
		if ($key === null) {
			$key = 'null';
		}

		// also works with null as key
		if (!empty($this->availableLanguages[$key])) {
			return $this->availableLanguages[$key];
		}

		$available = ['en']; //english is always available
		$dir = $this->findL10nDir($app);
		if (is_dir($dir)) {
			$files = scandir($dir);
			if ($files !== false) {
				foreach ($files as $file) {
					if (substr($file, -5) === '.json' && substr($file, 0, 4) !== 'l10n') {
						$available[] = substr($file, 0, -5);
					}
				}
			}
		}

		// merge with translations from theme
		$theme = $this->config->getSystemValue('theme');
		if (!empty($theme)) {
			$themeDir = $this->serverRoot . '/themes/' . $theme . substr($dir, strlen($this->serverRoot));

			if (is_dir($themeDir)) {
				$files = scandir($themeDir);
				if ($files !== false) {
					foreach ($files as $file) {
						if (substr($file, -5) === '.json' && substr($file, 0, 4) !== 'l10n') {
							$available[] = substr($file, 0, -5);
						}
					}
				}
			}
		}

		$this->availableLanguages[$key] = $available;
		return $available;
	}

	/**
	 * @param string|null $app App id or null for core
	 * @param string $lang
	 * @return bool
	 */
	public function languageExists($app, $lang) {
		if ($lang === 'en') {//english is always available
			return true;
		}

		$languages = $this->findAvailableLanguages($app);
		return array_search($lang, $languages) !== false;
	}

	/**
	 * @param string|null $app App id or null for core
	 * @return string
	 */
	public function setLanguageFromRequest($app = null) {
		$header = $this->request->getHeader('ACCEPT_LANGUAGE');
		if ($header) {
			$available = $this->findAvailableLanguages($app);

			// E.g. make sure that 'de' is before 'de_DE'.
			sort($available);

			$preferences = preg_split('/,\s*/', strtolower($header));
			foreach ($preferences as $preference) {
				list($preferred_language) = explode(';', $preference);
				$preferred_language = str_replace('-', '_', $preferred_language);

				foreach ($available as $available_language) {
					if ($preferred_language === strtolower($available_language)) {
						if ($app === null && !$this->requestLanguage) {
							$this->requestLanguage = $available_language;
						}
						return $available_language;
					}
				}

				// Fallback from de_De to de
				foreach ($available as $available_language) {
					if (substr($preferred_language, 0, 2) === $available_language) {
						if ($app === null && !$this->requestLanguage) {
							$this->requestLanguage = $available_language;
						}
						return $available_language;
					}
				}
			}
		}

		if ($app === null && !$this->requestLanguage) {
			$this->requestLanguage = 'en';
		}
		return 'en'; // Last try: English
	}

	/**
	 * Get a list of language files that should be loaded
	 *
	 * @param string $app
	 * @param string $lang
	 * @return string[]
	 */
	// FIXME This method is only public, until OC_L10N does not need it anymore,
	// FIXME This is also the reason, why it is not in the public interface
	public function getL10nFilesForApp($app, $lang) {
		$languageFiles = [];

		$i18nDir = $this->findL10nDir($app);
		$transFile = strip_tags($i18nDir) . strip_tags($lang) . '.json';

		if ((\OC_Helper::isSubDirectory($transFile, $this->serverRoot . '/core/l10n/')
				|| \OC_Helper::isSubDirectory($transFile, $this->serverRoot . '/lib/l10n/')
				|| \OC_Helper::isSubDirectory($transFile, $this->serverRoot . '/settings/l10n/')
				|| \OC_Helper::isSubDirectory($transFile, \OC_App::getAppPath($app) . '/l10n/')
			)
			&& file_exists($transFile)) {
			// load the translations file
			$languageFiles[] = $transFile;
		}

		// merge with translations from theme
		$theme = $this->config->getSystemValue('theme');
		if (!empty($theme)) {
			$transFile = $this->serverRoot . '/themes/' . $theme . substr($transFile, strlen($this->serverRoot));
			if (file_exists($transFile)) {
				$languageFiles[] = $transFile;
			}
		}

		return $languageFiles;
	}

	/**
	 * find the l10n directory
	 *
	 * @param string $app App id or empty string for core
	 * @return string directory
	 */
	protected function findL10nDir($app = null) {
		if (in_array($app, ['core', 'lib', 'settings'])) {
			if (file_exists($this->serverRoot . '/' . $app . '/l10n/')) {
				return $this->serverRoot . '/' . $app . '/l10n/';
			}
		} else if ($app && \OC_App::getAppPath($app) !== false) {
			// Check if the app is in the app folder
			return \OC_App::getAppPath($app) . '/l10n/';
		}
		return $this->serverRoot . '/core/l10n/';
	}


	/**
	 * Creates a function from the plural string
	 *
	 * Parts of the code is copied from Habari:
	 * https://github.com/habari/system/blob/master/classes/locale.php
	 * @param string $string
	 * @return string
	 */
	public function createPluralFunction($string) {
		if (isset($this->pluralFunctions[$string])) {
			return $this->pluralFunctions[$string];
		}

		if (preg_match( '/^\s*nplurals\s*=\s*(\d+)\s*;\s*plural=(.*)$/u', $string, $matches)) {
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
			$function = create_function('$n', $body);
			$this->pluralFunctions[$string] = $function;
			return $function;
		} else {
			// default: one plural form for all cases but n==1 (english)
			$function = create_function(
				'$n',
				'$nplurals=2;$plural=($n==1?0:1);return ($plural>=$nplurals?$nplurals-1:$plural);'
			);
			$this->pluralFunctions[$string] = $function;
			return $function;
		}
	}
}
