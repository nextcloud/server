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
 * Factory for creating language instances.
 */
class Factory implements IFactory {
	/**
	 * Cached resolved language per app context.
	 *
	 * @var array<string, string>
	 */
	protected array $requestLanguages = [];

	/**
	 * Cached L10N instances.
	 *
	 * Structure: app => language => localeKey => IL10N
	 *
	 * @var array<string, array<string, array<string, IL10N>>>
	 */
	protected array $instances = [];

	/**
	 * Cached available languages per app key.
	 *
	 * Structure: appKey => string[]
	 *
	 * @var array<string, string[]>
	 */
	protected array $availableLanguages = [];

	/**
	 * Membership map for available languages.
	 *
	 * Structure: appKey => languageCode => true
	 *
	 * This map is derived from $availableLanguages and may be built lazily in
	 * languageExists(). Code that updates $availableLanguages must either rebuild
	 * the corresponding map entry or let languageExists() do so on demand.
	 *
	 * @var array<string, array<string, true>>
	 */
	protected array $availableLanguageMap = [];

	/**
	 * Lookup cache for locale existence checks.
	 *
	 * Structure: localeCode => true
	 *
	 * @var array<string, bool>
	 */
	protected array $localeCache = [];

	/**
	 * Cached locale metadata loaded from resources/locales.json.
	 *
	 * @var array
	 */
	protected $availableLocales = [];

 	/**
	 * Cached plural rule callbacks by language.
	 *
	 * @var array<string, callable>
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

	/**
	 * Returns the normalized cache key used for app-scoped caches.
	 */
	private function getAppCacheKey(?string $app): string {
		return $app ?? '__core__';
	}

	/**
	 * Returns the normalized cache key used for locale-scoped caches.
	 */
	private function getLocaleCacheKey(?string $locale): string {
		return $locale ?? '__default__';
	}

	#[\Override]
	public function get($app, $lang = null, $locale = null) {
		return new LazyL10N(function () use ($app, $lang, $locale) {
			$app = $this->appManager->cleanAppId($app);
			$lang = $this->cleanLanguage($lang);

			$forcedLanguage = $this->cleanLanguage($this->request->getParam('forceLanguage'))
				?? $this->config->getSystemValue('force_language', false);
			if (is_string($forcedLanguage)) {
				$lang = $forcedLanguage;
			}

			$forcedLocale = $this->config->getSystemValue('force_locale', false);
			if (is_string($forcedLocale)) {
				$locale = $forcedLocale;
			}

			$lang = $this->validateLanguage($app, $lang);

			if ($locale === null || !$this->localeExists($locale)) {
				$locale = $this->findLocale($lang);
			}

			$localeCacheKey = $this->getLocaleCacheKey($locale);

			if (!isset($this->instances[$app][$lang][$localeCacheKey])) {
				$this->instances[$app][$lang][$localeCacheKey] = new L10N(
					$this,
					$app,
					$lang,
					$locale,
					$this->getL10nFilesForApp($app, $lang)
				);
			}

			return $this->instances[$app][$lang][$localeCacheKey];
		});
	}

	/**
	 * Removes unsupported characters before a value is used as a language code.
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
	private function cleanLanguage(?string $languageCode): ?string {
		if ($languageCode === null) {
			return null;
		}

		$languageCode = preg_replace('/[^a-zA-Z0-9.;,=_-]/', '', $languageCode);
		return str_replace('..', '', $languageCode);
	}

	/**
	 * Validates a language code for the given app.
	 *
	 * Returns the provided language code when available for the app; otherwise
	 * falls back to the best resolved language for that app.
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
	private function validateLanguage(string $appId, ?string $languageCode): string {
		if ($languageCode === null || !$this->languageExists($appId, $languageCode)) {
			return $this->findLanguage($appId);
		}

		return $languageCode;
	}

	#[\Override]
	public function findLanguage(?string $appId = null): string {
		$appCacheKey = $this->getAppCacheKey($appId);

		// Step 1: a forced language overrides any other source.
		$forcedLanguage = $this->cleanLanguage($this->request->getParam('forceLanguage'))
			?? $this->config->getSystemValue('force_language', false);
		if (is_string($forcedLanguage)) {
			$this->requestLanguages[$appCacheKey] = $forcedLanguage;
		}

		// Step 2: reuse the already resolved language for this app context.
		if (isset($this->requestLanguages[$appCacheKey]) && $this->languageExists($appId, $this->requestLanguages[$appCacheKey])) {
			return $this->requestLanguages[$appCacheKey];
		}

		// Step 3: User preference (if installed)
		//
		// Nextcloud may not be installed yet, so user preference lookup
		// can fail before the preferences table exists.
		//
		// @see https://github.com/owncloud/core/issues/21955
		if ($this->config->getSystemValueBool('installed', false)) {
			$currentUser = $this->userSession->getUser();
			$userId = $currentUser !== null ? $currentUser->getUID() : null;
			$userLanguage = $userId !== null ? $this->config->getUserValue($userId, 'core', 'lang', null) : null;
		} else {
			$userId = null;
			$userLanguage = null;
		}

		if ($userLanguage) {
			$this->requestLanguages[$appCacheKey] = $userLanguage;
			if ($this->languageExists($appId, $userLanguage)) {
				return $userLanguage;
			}
		}

		// Step 4: inspect the request headers.
		try {
			$resolvedLanguage = $this->getLanguageFromRequest($appId);
			$this->requestLanguages[$appCacheKey] = $resolvedLanguage;

			if ($userId !== null && $appId === null && !$userLanguage) {
				$this->config->setUserValue($userId, 'core', 'lang', $resolvedLanguage);
			}

			return $resolvedLanguage;
		} catch (LanguageNotFoundException $e) {
			// Fall back to default language (if available)
			$defaultLanguage = $this->config->getSystemValue('default_language', false);
			if ($defaultLanguage !== false && $this->languageExists($appId, $defaultLanguage)) {
				$this->requestLanguages[$appCacheKey] = $defaultLanguage;
				return $defaultLanguage;
			}
		}

		// Step 5: Fall back to English (last resort)
		$this->requestLanguages[$appCacheKey] = 'en';
		return 'en';
	}

	#[\Override]
	public function findGenericLanguage(?string $appId = null): string {
		$forcedLanguage = $this->cleanLanguage($this->request->getParam('forceLanguage'))
			?? $this->config->getSystemValue('force_language', false);
		if ($forcedLanguage !== false) {
			return $forcedLanguage;
		}

		$defaultLanguage = $this->config->getSystemValue('default_language', false);
		if ($defaultLanguage !== false && $this->languageExists($appId, $defaultLanguage)) {
			return $defaultLanguage;
		}

		return 'en';
	}

	#[\Override]
	public function findLocale($lang = null) {
		$forcedLocale = $this->config->getSystemValue('force_locale', false);
		if (is_string($forcedLocale) && $this->localeExists($forcedLocale)) {
			return $forcedLocale;
		}

		if ($this->config->getSystemValueBool('installed', false)) {
			$currentUser = $this->userSession->getUser();
			$userId = $currentUser !== null ? $currentUser->getUID() : null;
			$userLocale = $userId !== null ? $this->config->getUserValue($userId, 'core', 'locale', null) : null;
		} else {
			$userId = null;
			$userLocale = null;
		}

		if ($userLocale && $this->localeExists($userLocale)) {
			return $userLocale;
		}

		$defaultLocale = $this->config->getSystemValue('default_locale', false);
		if ($defaultLocale !== false && $this->localeExists($defaultLocale)) {
			return $defaultLocale;
		}

		// If no user locale set, use lang as locale
		if ($lang !== null && $this->localeExists($lang)) {
			return $lang;
		}

		// Fall back
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
		$languageCode = explode('_', $locale)[0];
		if ($this->languageExists($app, $languageCode)) {
			return $languageCode;
		}

		return null;
	}

	#[\Override]
	public function findAvailableLanguages($app = null): array {
		$appCacheKey = $this->getAppCacheKey($app);

		$cachedLanguages = $this->cache->get($appCacheKey);
		if (is_array($cachedLanguages)) {
			$this->availableLanguages[$appCacheKey] = $cachedLanguages;
			return $cachedLanguages;
		}

		if (!empty($this->availableLanguages[$appCacheKey])) {
			return $this->availableLanguages[$appCacheKey];
		}

		$availableLanguageSet = ['en' => true]; // English is always available
		$l10nDir = $this->findL10nDir($app);

		if (is_dir($l10nDir)) {
			$files = scandir($l10nDir);
			if ($files !== false) {
				foreach ($files as $fileName) {
					if (str_ends_with($fileName, '.json') && !str_starts_with($fileName, 'l10n')) {
						$availableLanguageSet[substr($fileName, 0, -5)] = true;
					}
				}
			}
		}

		// Merge translations from the active theme.
		$theme = $this->config->getSystemValueString('theme');
		if (!empty($theme)) {
			$themeL10nDir = $this->serverRoot . '/themes/' . $theme . substr($l10nDir, strlen($this->serverRoot));

			if (is_dir($themeL10nDir)) {
				$files = scandir($themeL10nDir);
				if ($files !== false) {
					foreach ($files as $fileName) {
						if (str_ends_with($fileName, '.json') && !str_starts_with($fileName, 'l10n')) {
							$availableLanguageSet[substr($fileName, 0, -5)] = true;
						}
					}
				}
			}
		}

		$availableLanguages = array_keys($availableLanguageSet);
		sort($availableLanguages);
		
		$this->availableLanguages[$appCacheKey] = $availableLanguages;
		$this->availableLanguageMap[$appCacheKey] = array_fill_keys($availableLanguages, true);
		$this->cache->set($appCacheKey, $availableLanguages, 60);

		return $availableLanguages;
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
		if ($lang === 'en') {
			return true;
		}

		$appCacheKey = $this->getAppCacheKey($app);

		if (!isset($this->availableLanguageMap[$appCacheKey])) {
			if (!isset($this->availableLanguages[$appCacheKey])) {
				$this->findAvailableLanguages($app);
			}

			// The membership map is derived lazily from the cached language list.
			$this->availableLanguageMap[$appCacheKey] = array_fill_keys(
				$this->availableLanguages[$appCacheKey] ?? [],
				true
			);
		}

		return isset($this->availableLanguageMap[$appCacheKey][$lang]);
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
		$forcedLanguage = $this->config->getSystemValue('force_language', false);
		if ($forcedLanguage !== false) {
			return $forcedLanguage;
		}

		if ($user instanceof IUser) {
			$userLanguage = $this->config->getUserValue($user->getUID(), 'core', 'lang', null);
			if ($userLanguage !== null) {
				return $userLanguage;
			}

			$forcedRequestLanguage = $this->cleanLanguage($this->request->getParam('forceLanguage'));
			if ($forcedRequestLanguage !== null) {
				return $forcedRequestLanguage;
			}

			// Use the request language for the currently authenticated user.
			if ($this->userSession->getUser() instanceof IUser
				&& $user->getUID() === $this->userSession->getUser()->getUID()) {
				try {
					return $this->getLanguageFromRequest();
				} catch (LanguageNotFoundException $e) {
				}
			}
		}

		return $this->cleanLanguage($this->request->getParam('forceLanguage'))
			?? $this->config->getSystemValueString('default_language', 'en');
	}

	#[\Override]
	public function localeExists($locale) {
		if ($locale === 'en') { // English is always available
			return true;
		}

		if ($this->localeCache === []) {
			$availableLocales = $this->findAvailableLocales();
			foreach ($availableLocales as $localeDefinition) {
				$this->localeCache[$localeDefinition['code']] = true;
			}
		}

		return isset($this->localeCache[$locale]);
	}

	/**
	 * Resolve the best language from the Accept-Language request header.
	 *
	 * @param string|null $app App id or null for core
	 * @return string
	 * @throws LanguageNotFoundException When no matching language can be resolved
	 */
	private function getLanguageFromRequest(?string $app = null): string {
		$acceptLanguageHeader = $this->cleanLanguage($this->request->getHeader('ACCEPT_LANGUAGE'));
		if ($acceptLanguageHeader !== '') {
			$availableLanguages = $this->findAvailableLanguages($app);

			// Ensure generic language codes are checked before region-specific ones, e.g. de before de_DE.
			sort($availableLanguages);

			$languagePreferences = preg_split('/,\s*/', strtolower($acceptLanguageHeader));
			foreach ($languagePreferences as $languagePreference) {
				[$preferredLanguage] = explode(';', $languagePreference);
				$preferredLanguage = str_replace('-', '_', $preferredLanguage);

				$preferredLanguageParts = explode('_', $preferredLanguage);
				foreach ($availableLanguages as $availableLanguage) {
					if ($preferredLanguage === strtolower($availableLanguage)) {
						return $this->respectDefaultLanguage($app, $availableLanguage);
					}

					if (strtolower($availableLanguage) === $preferredLanguageParts[0] . '_' . end($preferredLanguageParts)) {
						return $availableLanguage;
					}
				}

				// Fallback from a region-specific locale, e.g. de_DE => de.
				foreach ($availableLanguages as $availableLanguage) {
					if ($preferredLanguageParts[0] === $availableLanguage) {
						return $availableLanguage;
					}
				}
			}
		}

		throw new LanguageNotFoundException();
	}

	/**
	 * Prefer the configured default language when it provides a more specific match.
	 *
	 * For example, if the browser requests `de` (non-formal German) and the instance
	 * default language is `de_DE` (formal German), prefer `de_DE` when that translation
	 * exists.
	 */
	protected function respectDefaultLanguage(?string $app, string $lang): string {
		$resolvedLanguage = $lang;
		$defaultLanguage = $this->config->getSystemValue('default_language', false);

		if (
			is_string($defaultLanguage)
			&& strtolower($lang) === 'de'
			&& strtolower($defaultLanguage) === 'de_de'
			&& $this->languageExists($app, 'de_DE')
		) {
			$resolvedLanguage = 'de_DE';
		}

		return $resolvedLanguage;
	}

	/**
	 * Checks whether a path is inside the given parent directory.
	 *
	 * This also rejects paths containing `..`.
	 */
	private function isSubDirectory(string $path, string $parentDirectory): bool {
		if (str_contains($path, '..')) {
			return false;
		}

		return str_starts_with($path, $parentDirectory);
	}

	/**
	 * Return the translation files to load for an app and language.
	 *
	 * Includes the base translation file and, when present, the corresponding
	 * theme override file.
	 *
	 * @param string $app
	 * @param string $lang
	 * @return string[]
	 */
	private function getL10nFilesForApp(string $app, string $lang): array {
		$languageFiles = [];

		$l10nDir = $this->findL10nDir($app);
		$translationFile = strip_tags($l10nDir) . strip_tags($lang) . '.json';

		if (($this->isSubDirectory($translationFile, $this->serverRoot . '/core/l10n/')
				|| $this->isSubDirectory($translationFile, $this->serverRoot . '/lib/l10n/')
				|| $this->isSubDirectory($translationFile, $l10nDir))
			&& file_exists($translationFile)
		) {
			$languageFiles[] = $translationFile;
		}

		// Merge translations from the active theme.
		$theme = $this->config->getSystemValueString('theme');
		if (!empty($theme)) {
			$themeTranslationFile = $this->serverRoot . '/themes/' . $theme . substr($translationFile, strlen($this->serverRoot));
			if (file_exists($themeTranslationFile)) {
				$languageFiles[] = $themeTranslationFile;
			}
		}

		return $languageFiles;
	}

	/**
	 * Return the l10n directory for an app.
	 *
	 * For `core` and `lib`, use the corresponding built-in directory when present.
	 * For other apps, resolve the app path and append `/l10n/`.
	 * Falls back to the core l10n directory when the app cannot be resolved.
	 *
	 * @param string|null $app App id or null for core
	 */
	protected function findL10nDir(?string $app = null): string {
		if (in_array($app, ['core', 'lib'], true)) {
			if (file_exists($this->serverRoot . '/' . $app . '/l10n/')) {
				return $this->serverRoot . '/' . $app . '/l10n/';
			}
		} elseif ($app) {
			try {
				return $this->appManager->getAppPath($app) . '/l10n/';
			} catch (AppPathNotFoundException) {
				// App not found, fall through to the core l10n directory.
			}
		}
		return $this->serverRoot . '/core/l10n/';
	}

	#[\Override]
	public function getLanguages(): array {
		$forcedLanguage = $this->config->getSystemValue('force_language', false);
		if ($forcedLanguage !== false) {
			$l10n = $this->get('lib', $forcedLanguage);
			$languageName = $l10n->t('__language_name__');

			return [
				'commonLanguages' => [[
					'code' => $forcedLanguage,
					'name' => $languageName,
				]],
				'otherLanguages' => [],
			];
		}

		$languageCodes = $this->findAvailableLanguages();
		$reducedLanguageCodes = $this->config->getSystemValue('reduce_to_languages', []);
		if (!empty($reducedLanguageCodes)) {
			$languageCodes = array_intersect($languageCodes, $reducedLanguageCodes);
		}

		$commonLanguages = [];
		$otherLanguages = [];

		foreach ($languageCodes as $languageCode) {
			$l10n = $this->get('lib', $languageCode);
			// TRANSLATORS: this is the language name for the language switcher in the personal settings and should be the localized version
			$languageName = $l10n->t('__language_name__');

			if ($l10n->getLanguageCode() === $languageCode && $languageName[0] !== '_') { // first check if the language name is in the translation file
				$languageEntry = [
					'code' => $languageCode,
					'name' => $languageName,
				];
			} elseif ($languageCode === 'en') {
				$languageEntry = [
					'code' => $languageCode,
					'name' => 'English (US)'
				];
			} else { // fallback to language code
				$languageEntry = [
					'code' => $languageCode,
					'name' => $languageCode,
				];
			}

			// put appropriate languages into appropriate arrays, to print them sorted
			// common languages -> divider -> other languages
			if (in_array($languageCode, self::COMMON_LANGUAGE_CODES, true)) {
				$commonLanguages[array_search($languageCode, self::COMMON_LANGUAGE_CODES, true)] = $languageEntry;
			} else {
				$otherLanguages[] = $languageEntry;
			}
		}

		ksort($commonLanguages);

		// Sort by display name rather than language code.
		usort($otherLanguages, function ($left, $right) {
			if ($left['code'] === $left['name'] && $right['code'] !== $right['name']) {
				// If left doesn't have a name, but right does, list right before left
				return 1;
			}
			if ($left['code'] !== $left['name'] && $right['code'] === $right['name']) {
				// If left does have a name, but right doesn't, list left before right
				return -1;
			}

			// Otherwise compare the names
			return strcmp($left['name'], $right['name']);
		});

		return [
			// reset indexes
			'commonLanguages' => array_values($commonLanguages),
			'otherLanguages' => $otherLanguages
		];
	}
}
