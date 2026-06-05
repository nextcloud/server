<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\L10N;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\L10N\ILanguageIterator;
use function is_null;

/**
 * A factory that generates language instances
 */
class Factory implements IFactory {
	/**
	 * Cached request language per app key.
	 *
	 * @var array<string, string>
	 */
	protected array $requestLanguages = [];

	/**
	 * Cached instances.
	 *
	 * Structure: App => Lang => Locale => \OCP\IL10N
	 *
	 * @var array<string, array<string, array<string, IL10N>>>
	 */
	protected array $instances = [];

	/**
	 * @var array<string, string[]>
	 */
	protected array $availableLanguages = [];

	/**
	 * Membership map for available languages.
	 *
	 * Structure: AppKey => Lang => true
	 *
	 * @var array<string, array<string, true>>
	 */
	protected array $availableLanguageMap = [];

	/**
	 * @var array<string, bool>
	 */
	protected array $localeCache = [];

	/**
	 * @var array
	 */
	protected $availableLocales = [];

 	/**
	 * @var array Structure: string => callable
	 * @var array
 	 */
	protected $pluralFunctions = [];

	public const COMMON_LANGUAGE_CODES = [
		'en', 'es', 'fr', 'de', 'de_DE', 'ja', 'ar', 'ru', 'nl', 'it',
		'pt_BR', 'pt_PT', 'da', 'fi_FI', 'nb_NO', 'sv', 'tr', 'zh_CN', 'ko'
	];

	/**
	 * Keep in sync with `build/translation-checker.php`
	 */
	public const RTL_LANGUAGES = [
		'ar', // Arabic
		'fa', // Persian
		'he', // Hebrew
		'ps', // Pashto,
		'ug', // 'Uyghurche / Uyghur
		'ur_PK', // Urdu
	];

	private ICache $cache;

	public function __construct(
		protected IConfig $config,
		protected IRequest $request,
		protected IUserSession $userSession,
		ICacheFactory $cacheFactory,
		protected string $serverRoot,
		protected IAppManager $appManager,
	) {
		$this->cache = $cacheFactory->createLocal('L10NFactory');
	}

	private function getAppKey(?string $app): string {
		return $app ?? '__core__';
	}

	private function getLocaleKey(?string $locale): string {
		return $locale ?? '__default__';
	}

	#[\Override]
	public function get($app, $lang = null, $locale = null) {
		return new LazyL10N(function () use ($app, $lang, $locale) {
			$app = $this->appManager->cleanAppId($app);
			$lang = $this->cleanLanguage($lang);

			$forceLang = $this->cleanLanguage($this->request->getParam('forceLanguage'))
				?? $this->config->getSystemValue('force_language', false);
			if (is_string($forceLang)) {
				$lang = $forceLang;
			}

			$forceLocale = $this->config->getSystemValue('force_locale', false);
			if (is_string($forceLocale)) {
				$locale = $forceLocale;
			}

			$lang = $this->validateLanguage($app, $lang);

			if ($locale === null || !$this->localeExists($locale)) {
				$locale = $this->findLocale($lang);
			}

			$localeKey = $this->getLocaleKey($locale);

			if (!isset($this->instances[$app][$lang][$localeKey])) {
				$this->instances[$app][$lang][$localeKey] = new L10N(
					$this,
					$app,
					$lang,
					$locale,
					$this->getL10nFilesForApp($app, $lang)
				);
			}

			return $this->instances[$app][$lang][$localeKey];
		});
	}

	/**
	 * Remove some invalid characters before using a string as a language
	 *
	 * @psalm-taint-escape callable
	 * @psalm-taint-escape cookie
	 * @psalm-taint-escape file
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape header
	 * @psalm-taint-escape html
	 * @psalm-taint-escape include
	 * @psalm-taint-escape ldap
	 * @psalm-taint-escape shell
	 * @psalm-taint-escape sql
	 * @psalm-taint-escape unserialize
	 */
	private function cleanLanguage(?string $lang): ?string {
		if ($lang === null) {
			return null;
		}
		$lang = preg_replace('/[^a-zA-Z0-9.;,=_-]/', '', $lang);
		return str_replace('..', '', $lang);
	}

	/**
	 * Check that $lang is an existing language and not null, otherwise return the language to use instead
	 *
	 * @psalm-taint-escape callable
	 * @psalm-taint-escape cookie
	 * @psalm-taint-escape file
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape header
	 * @psalm-taint-escape html
	 * @psalm-taint-escape include
	 * @psalm-taint-escape ldap
	 * @psalm-taint-escape shell
	 * @psalm-taint-escape sql
	 * @psalm-taint-escape unserialize
	 */
	private function validateLanguage(string $app, ?string $lang): string {
		if ($lang === null || !$this->languageExists($app, $lang)) {
			return $this->findLanguage($app);
		}
		return $lang;
	}

	#[\Override]
	public function findLanguage(?string $appId = null): string {
		$appKey = $this->getAppKey($appId);

		// Step 1: Forced language always has precedence over anything else
		$forceLang = $this->cleanLanguage($this->request->getParam('forceLanguage'))
			?? $this->config->getSystemValue('force_language', false);
		if (is_string($forceLang)) {
			$this->requestLanguages[$appKey] = $forceLang;
		}

		// Step 2: Return cached language for this app context
		if (isset($this->requestLanguages[$appKey]) && $this->languageExists($appId, $this->requestLanguages[$appKey])) {
			return $this->requestLanguages[$appKey];
		}

		// Step 3: User preference (only if installed)
		if ($this->config->getSystemValueBool('installed', false)) {
			$userId = !is_null($this->userSession->getUser()) ? $this->userSession->getUser()->getUID() :  null;
			if (!is_null($userId)) {
				$userLang = $this->config->getUserValue($userId, 'core', 'lang', null);
			} else {
				$userLang = null;
			}
		} else {
			$userId = null;
			$userLang = null;
		}

		if ($userLang) {
			$this->requestLanguages[$appKey] = $userLang;
			if ($this->languageExists($appId, $userLang)) {
				return $userLang;
			}
		}

		// Step 4: Check the request headers
		try {
			$lang = $this->getLanguageFromRequest($appId);
			$this->requestLanguages[$appKey] = $lang;

			if ($userId !== null && $appId === null && !$userLang) {
				$this->config->setUserValue($userId, 'core', 'lang', $lang);
			}

			return $lang;
		} catch (LanguageNotFoundException $e) {
			// Fall back to default language (if available)
			$defaultLanguage = $this->config->getSystemValue('default_language', false);
			if ($defaultLanguage !== false && $this->languageExists($appId, $defaultLanguage)) {
				$this->requestLanguages[$appKey] = $defaultLanguage;
				return $defaultLanguage;
			}
		}

		// Step 5: Fall back to English (last resort)
		$this->requestLanguages[$appKey] = 'en';
		return 'en';
	}

	#[\Override]
	public function findGenericLanguage(?string $appId = null): string {
		// Step 1: Forced language always has precedence over anything else
		$forcedLanguage = $this->cleanLanguage($this->request->getParam('forceLanguage')) ?? $this->config->getSystemValue('force_language', false);
		if ($forcedLanguage !== false) {
			return $forcedLanguage;
		}

		// Step 2: Check if we have a default language
		$defaultLanguage = $this->config->getSystemValue('default_language', false);
		if ($defaultLanguage !== false && $this->languageExists($appId, $defaultLanguage)) {
			return $defaultLanguage;
		}

		// Step 3: fall back to English
		return 'en';
	}

	#[\Override]
	public function findLocale($lang = null) {
		$forceLocale = $this->config->getSystemValue('force_locale', false);
		if (is_string($forceLocale) && $this->localeExists($forceLocale)) {
			return $forceLocale;
		}

		if ($this->config->getSystemValueBool('installed', false)) {
			$userId = $this->userSession->getUser() !== null ? $this->userSession->getUser()->getUID() :  null;
			$userLocale = null;
			if ($userId !== null) {
				$userLocale = $this->config->getUserValue($userId, 'core', 'locale', null);
			}
		} else {
			$userId = null;
			$userLocale = null;
		}

		if ($userLocale && $this->localeExists($userLocale)) {
			return $userLocale;
		}

		// Default : use system default locale
		$defaultLocale = $this->config->getSystemValue('default_locale', false);
		if ($defaultLocale !== false && $this->localeExists($defaultLocale)) {
			return $defaultLocale;
		}

		// If no user locale set, use lang as locale
		if ($lang !== null && $this->localeExists($lang)) {
			return $lang;
		}

		// At last, return USA
		return 'en_US';
	}

	#[\Override]
	public function findLanguageFromLocale(string $app = 'core', ?string $locale = null) {
		if ($locale === null || $locale === '') {
			return null;
		}

		if ($this->languageExists($app, $locale)) {
			return $locale;
		}

		// Try to split e.g: fr_FR => fr
		$locale = explode('_', $locale)[0];
		if ($this->languageExists($app, $locale)) {
			return $locale;
		}

		return null;
	}

	#[\Override]
	public function findAvailableLanguages($app = null): array {
		$key = $this->getAppKey($app);

		$availableLanguages = $this->cache->get($key);
		if (is_array($availableLanguages)) {
			$this->availableLanguages[$key] = $availableLanguages;
			$this->availableLanguageMap[$key] = array_fill_keys($availableLanguages, true);
		}

		if (!empty($this->availableLanguages[$key])) {
			return $this->availableLanguages[$key];
		}

		$availableSet = ['en' => true]; // English is always available
		$dir = $this->findL10nDir($app);

		if (is_dir($dir)) {
			$files = scandir($dir);
			if ($files !== false) {
				foreach ($files as $file) {
					if (str_ends_with($file, '.json') && !str_starts_with($file, 'l10n')) {
						$availableSet[substr($file, 0, -5)] = true;
					}
				}
			}
		}

		// merge with translations from theme
		$theme = $this->config->getSystemValueString('theme');
		if (!empty($theme)) {
			$themeDir = $this->serverRoot . '/themes/' . $theme . substr($dir, strlen($this->serverRoot));

			if (is_dir($themeDir)) {
				$files = scandir($themeDir);
				if ($files !== false) {
					foreach ($files as $file) {
						if (str_ends_with($file, '.json') && !str_starts_with($file, 'l10n')) {
							$availableSet[substr($file, 0, -5)] = true;
						}
					}
				}
			}
		}

		$available = array_keys($availableSet);
		sort($available);
		
		$this->availableLanguages[$key] = $available;
		$this->availableLanguageMap[$key] = array_fill_keys($available, true);
		$this->cache->set($key, $available, 60);

		return $available;
	}

	#[\Override]
	public function findAvailableLocales() {
		if (!empty($this->availableLocales)) {
			return $this->availableLocales;
		}

		$localeData = file_get_contents(\OC::$SERVERROOT . '/resources/locales.json');
		$this->availableLocales = \json_decode($localeData, true);

		return $this->availableLocales;
	}

	#[\Override]
	public function languageExists($app, $lang) {
		if ($lang === 'en') { //english is always available
			return true;
		}

		$key = $this->getAppKey($app);
		if (!isset($this->availableLanguageMap[$key])) {
			$this->findAvailableLanguages($app);
		}

		return isset($this->availableLanguageMap[$key][$lang]);
	}

	#[\Override]
	public function getLanguageDirection(string $language): string {
		if (in_array($language, self::RTL_LANGUAGES, true)) {
			return 'rtl';
		}

		return 'ltr';
	}

	#[\Override]
	public function getLanguageIterator(?IUser $user = null): ILanguageIterator {
		$user = $user ?? $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('Failed to get an IUser instance');
		}
		return new LanguageIterator($user, $this->config);
	}

	#[\Override]
	public function getUserLanguage(?IUser $user = null): string {
		$language = $this->config->getSystemValue('force_language', false);
		if ($language !== false) {
			return $language;
		}

		if ($user instanceof IUser) {
			$language = $this->config->getUserValue($user->getUID(), 'core', 'lang', null);
			if ($language !== null) {
				return $language;
			}

			$forcedLanguage = $this->cleanLanguage($this->request->getParam('forceLanguage'));
			if ($forcedLanguage !== null) {
				return $forcedLanguage;
			}

			// Use language from request
			if ($this->userSession->getUser() instanceof IUser
				&& $user->getUID() === $this->userSession->getUser()->getUID()) {
				try {
					return $this->getLanguageFromRequest();
				} catch (LanguageNotFoundException $e) {
				}
			}
		}

		return $this->cleanLanguage($this->request->getParam('forceLanguage')) ?? $this->config->getSystemValueString('default_language', 'en');
	}

	#[\Override]
	public function localeExists($locale) {
		if ($locale === 'en') { //english is always available
			return true;
		}

		if ($this->localeCache === []) {
			$locales = $this->findAvailableLocales();
			foreach ($locales as $l) {
				$this->localeCache[$l['code']] = true;
			}
		}

		return isset($this->localeCache[$locale]);
	}

	/**
	 * @throws LanguageNotFoundException
	 */
	private function getLanguageFromRequest(?string $app = null): string {
		$header = $this->cleanLanguage($this->request->getHeader('ACCEPT_LANGUAGE'));
		if ($header !== '') {
			$available = $this->findAvailableLanguages($app);

			// E.g. make sure that 'de' is before 'de_DE'.
			sort($available);

			$preferences = preg_split('/,\s*/', strtolower($header));
			foreach ($preferences as $preference) {
				[$preferred_language] = explode(';', $preference);
				$preferred_language = str_replace('-', '_', $preferred_language);

				$preferred_language_parts = explode('_', $preferred_language);
				foreach ($available as $available_language) {
					if ($preferred_language === strtolower($available_language)) {
						return $this->respectDefaultLanguage($app, $available_language);
					}
					if (strtolower($available_language) === $preferred_language_parts[0] . '_' . end($preferred_language_parts)) {
						return $available_language;
					}
				}

				// Fallback from de_De to de
				foreach ($available as $available_language) {
					if ($preferred_language_parts[0] === $available_language) {
						return $available_language;
					}
				}
			}
		}

		throw new LanguageNotFoundException();
	}

	/**
	 * if default language is set to de_DE (formal German) this should be
	 * preferred to 'de' (non-formal German) if possible
	 */
	protected function respectDefaultLanguage(?string $app, string $lang): string {
		$result = $lang;
		$defaultLanguage = $this->config->getSystemValue('default_language', false);

		// use formal version of german ("Sie" instead of "Du") if the default
		// language is set to 'de_DE' if possible
		if (
			is_string($defaultLanguage)
			&& strtolower($lang) === 'de'
			&& strtolower($defaultLanguage) === 'de_de'
			&& $this->languageExists($app, 'de_DE')
		) {
			$result = 'de_DE';
		}

		return $result;
	}

	/**
	 * Checks if $sub is a subdirectory of $parent
	 *
	 * @param string $sub
	 * @param string $parent
	 * @return bool
	 */
	private function isSubDirectory($sub, $parent) {
		// Check whether $sub contains no ".."
		if (str_contains($sub, '..')) {
			return false;
		}

		// Check whether $sub is a subdirectory of $parent
		if (str_starts_with($sub, $parent)) {
			return true;
		}

		return false;
	}

	/**
	 * Get a list of language files that should be loaded
	 *
	 * @return string[]
	 */
	private function getL10nFilesForApp(string $app, string $lang): array {
		$languageFiles = [];

		$i18nDir = $this->findL10nDir($app);
		$transFile = strip_tags($i18nDir) . strip_tags($lang) . '.json';

		if (($this->isSubDirectory($transFile, $this->serverRoot . '/core/l10n/')
				|| $this->isSubDirectory($transFile, $this->serverRoot . '/lib/l10n/')
				|| $this->isSubDirectory($transFile, $this->appManager->getAppPath($app) . '/l10n/'))
			&& file_exists($transFile)
		) {
			// load the translations file
			$languageFiles[] = $transFile;
		}

		// merge with translations from theme
		$theme = $this->config->getSystemValueString('theme');
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
		if (in_array($app, ['core', 'lib'])) {
			if (file_exists($this->serverRoot . '/' . $app . '/l10n/')) {
				return $this->serverRoot . '/' . $app . '/l10n/';
			}
		} elseif ($app) {
			try {
				return $this->appManager->getAppPath($app) . '/l10n/';
			} catch (AppPathNotFoundException) {
				/* App not found, continue */
			}
		}
		return $this->serverRoot . '/core/l10n/';
	}

	#[\Override]
	public function getLanguages(): array {
		$forceLanguage = $this->config->getSystemValue('force_language', false);
		if ($forceLanguage !== false) {
			$l = $this->get('lib', $forceLanguage);
			$potentialName = $l->t('__language_name__');

			return [
				'commonLanguages' => [[
					'code' => $forceLanguage,
					'name' => $potentialName,
				]],
				'otherLanguages' => [],
			];
		}

		$languageCodes = $this->findAvailableLanguages();
		$reduceToLanguages = $this->config->getSystemValue('reduce_to_languages', []);
		if (!empty($reduceToLanguages)) {
			$languageCodes = array_intersect($languageCodes, $reduceToLanguages);
		}

		$commonLanguages = [];
		$otherLanguages = [];

		foreach ($languageCodes as $lang) {
			$l = $this->get('lib', $lang);
			// TRANSLATORS this is the language name for the language switcher in the personal settings and should be the localized version
			$potentialName = $l->t('__language_name__');
			if ($l->getLanguageCode() === $lang && $potentialName[0] !== '_') { //first check if the language name is in the translation file
				$ln = [
					'code' => $lang,
					'name' => $potentialName
				];
			} elseif ($lang === 'en') {
				$ln = [
					'code' => $lang,
					'name' => 'English (US)'
				];
			} else { //fallback to language code
				$ln = [
					'code' => $lang,
					'name' => $lang
				];
			}

			// put appropriate languages into appropriate arrays, to print them sorted
			// common languages -> divider -> other languages
			if (in_array($lang, self::COMMON_LANGUAGE_CODES)) {
				$commonLanguages[array_search($lang, self::COMMON_LANGUAGE_CODES, true)] = $ln;
			} else {
				$otherLanguages[] = $ln;
			}
		}

		ksort($commonLanguages);

		// sort now by displayed language not the iso-code
		usort($otherLanguages, function ($a, $b) {
			if ($a['code'] === $a['name'] && $b['code'] !== $b['name']) {
				// If a doesn't have a name, but b does, list b before a
				return 1;
			}
			if ($a['code'] !== $a['name'] && $b['code'] === $b['name']) {
				// If a does have a name, but b doesn't, list a before b
				return -1;
			}
			// Otherwise compare the names
			return strcmp($a['name'], $b['name']);
		});

		return [
			// reset indexes
			'commonLanguages' => array_values($commonLanguages),
			'otherLanguages' => $otherLanguages
		];
	}
}
