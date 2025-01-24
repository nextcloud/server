<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\AppFramework\Http\Request;
use OC\Authentication\Token\IProvider;
use OC\Files\FilenameValidator;
use OC\Search\SearchQuery;
use OC\Template\CSSResourceLocator;
use OC\Template\JSConfigHelper;
use OC\Template\JSResourceLocator;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;

class TemplateLayout extends \OC_Template {
	private static $versionHash = '';
	/** @var string[] */
	private static $cacheBusterCache = [];

	/** @var CSSResourceLocator|null */
	public static $cssLocator = null;

	/** @var JSResourceLocator|null */
	public static $jsLocator = null;

	private IConfig $config;
	private IAppManager $appManager;
	private InitialStateService $initialState;
	private INavigationManager $navigationManager;

	/**
	 * @param string $renderAs
	 * @param string $appId application id
	 */
	public function __construct($renderAs, $appId = '') {
		$this->config = \OCP\Server::get(IConfig::class);
		$this->appManager = \OCP\Server::get(IAppManager::class);
		$this->initialState = \OCP\Server::get(InitialStateService::class);
		$this->navigationManager = \OCP\Server::get(INavigationManager::class);

		// Add fallback theming variables if not rendered as user
		if ($renderAs !== TemplateResponse::RENDER_AS_USER) {
			// TODO cache generated default theme if enabled for fallback if server is erroring ?
			Util::addStyle('theming', 'default');
		}

		// Decide which page we show
		if ($renderAs === TemplateResponse::RENDER_AS_USER) {
			parent::__construct('core', 'layout.user');
			if (in_array(\OC_App::getCurrentApp(), ['settings','admin', 'help']) !== false) {
				$this->assign('bodyid', 'body-settings');
			} else {
				$this->assign('bodyid', 'body-user');
			}

			$this->initialState->provideInitialState('core', 'active-app', $this->navigationManager->getActiveEntry());
			$this->initialState->provideInitialState('core', 'apps', array_values($this->navigationManager->getAll()));

			if ($this->config->getSystemValueBool('unified_search.enabled', false) || !$this->config->getSystemValueBool('enable_non-accessible_features', true)) {
				$this->initialState->provideInitialState('unified-search', 'limit-default', (int)$this->config->getAppValue('core', 'unified-search.limit-default', (string)SearchQuery::LIMIT_DEFAULT));
				$this->initialState->provideInitialState('unified-search', 'min-search-length', (int)$this->config->getAppValue('core', 'unified-search.min-search-length', (string)1));
				$this->initialState->provideInitialState('unified-search', 'live-search', $this->config->getAppValue('core', 'unified-search.live-search', 'yes') === 'yes');
				Util::addScript('core', 'legacy-unified-search', 'core');
			} else {
				Util::addScript('core', 'unified-search', 'core');
			}
			// Set body data-theme
			$this->assign('enabledThemes', []);
			if ($this->appManager->isEnabledForUser('theming') && class_exists('\OCA\Theming\Service\ThemesService')) {
				/** @var \OCA\Theming\Service\ThemesService */
				$themesService = \OC::$server->get(\OCA\Theming\Service\ThemesService::class);
				$this->assign('enabledThemes', $themesService->getEnabledThemes());
			}

			// Set logo link target
			$logoUrl = $this->config->getSystemValueString('logo_url', '');
			$this->assign('logoUrl', $logoUrl);

			// Set default entry name
			$defaultEntryId = $this->navigationManager->getDefaultEntryIdForUser();
			$defaultEntry = $this->navigationManager->get($defaultEntryId);
			$this->assign('defaultAppName', $defaultEntry['name']);

			// Add navigation entry
			$this->assign('application', '');
			$this->assign('appid', $appId);

			$navigation = $this->navigationManager->getAll();
			$this->assign('navigation', $navigation);
			$settingsNavigation = $this->navigationManager->getAll('settings');
			$this->initialState->provideInitialState('core', 'settingsNavEntries', $settingsNavigation);

			foreach ($navigation as $entry) {
				if ($entry['active']) {
					$this->assign('application', $entry['name']);
					break;
				}
			}

			foreach ($settingsNavigation as $entry) {
				if ($entry['active']) {
					$this->assign('application', $entry['name']);
					break;
				}
			}

			$userDisplayName = false;
			$user = \OC::$server->get(IUserSession::class)->getUser();
			if ($user) {
				$userDisplayName = $user->getDisplayName();
			}
			$this->assign('user_displayname', $userDisplayName);
			$this->assign('user_uid', \OC_User::getUser());

			if ($user === null) {
				$this->assign('userAvatarSet', false);
				$this->assign('userStatus', false);
			} else {
				$this->assign('userAvatarSet', true);
				$this->assign('userAvatarVersion', $this->config->getUserValue(\OC_User::getUser(), 'avatar', 'version', 0));
			}
		} elseif ($renderAs === TemplateResponse::RENDER_AS_ERROR) {
			parent::__construct('core', 'layout.guest', '', false);
			$this->assign('bodyid', 'body-login');
			$this->assign('user_displayname', '');
			$this->assign('user_uid', '');
		} elseif ($renderAs === TemplateResponse::RENDER_AS_GUEST) {
			parent::__construct('core', 'layout.guest');
			\OC_Util::addStyle('guest');
			$this->assign('bodyid', 'body-login');

			$userDisplayName = false;
			$user = \OC::$server->get(IUserSession::class)->getUser();
			if ($user) {
				$userDisplayName = $user->getDisplayName();
			}
			$this->assign('user_displayname', $userDisplayName);
			$this->assign('user_uid', \OC_User::getUser());
		} elseif ($renderAs === TemplateResponse::RENDER_AS_PUBLIC) {
			parent::__construct('core', 'layout.public');
			$this->assign('appid', $appId);
			$this->assign('bodyid', 'body-public');

			// Set body data-theme
			$this->assign('enabledThemes', []);
			if ($this->appManager->isEnabledForUser('theming') && class_exists('\OCA\Theming\Service\ThemesService')) {
				/** @var \OCA\Theming\Service\ThemesService $themesService */
				$themesService = \OC::$server->get(\OCA\Theming\Service\ThemesService::class);
				$this->assign('enabledThemes', $themesService->getEnabledThemes());
			}

			// Set logo link target
			$logoUrl = $this->config->getSystemValueString('logo_url', '');
			$this->assign('logoUrl', $logoUrl);

			/** @var IRegistry $subscription */
			$subscription = \OCP\Server::get(IRegistry::class);
			$showSimpleSignup = $this->config->getSystemValueBool('simpleSignUpLink.shown', true);
			if ($showSimpleSignup && $subscription->delegateHasValidSubscription()) {
				$showSimpleSignup = false;
			}

			$defaultSignUpLink = 'https://nextcloud.com/signup/';
			$signUpLink = $this->config->getSystemValueString('registration_link', $defaultSignUpLink);
			if ($signUpLink !== $defaultSignUpLink) {
				$showSimpleSignup = true;
			}

			if ($this->appManager->isEnabledForUser('registration')) {
				$urlGenerator = \OCP\Server::get(IURLGenerator::class);
				$signUpLink = $urlGenerator->getAbsoluteURL('/index.php/apps/registration/');
			}

			$this->assign('showSimpleSignUpLink', $showSimpleSignup);
			$this->assign('signUpLink', $signUpLink);
		} else {
			parent::__construct('core', 'layout.base');
		}
		// Send the language, locale, and direction to our layouts
		$lang = \OC::$server->get(IFactory::class)->findLanguage();
		$locale = \OC::$server->get(IFactory::class)->findLocale($lang);
		$direction = \OC::$server->getL10NFactory()->getLanguageDirection($lang);

		$lang = str_replace('_', '-', $lang);
		$this->assign('language', $lang);
		$this->assign('locale', $locale);
		$this->assign('direction', $direction);

		if ($this->config->getSystemValueBool('installed', false)) {
			if (empty(self::$versionHash)) {
				$v = \OC_App::getAppVersions();
				$v['core'] = implode('.', \OCP\Util::getVersion());
				self::$versionHash = substr(md5(implode(',', $v)), 0, 8);
			}
		} else {
			self::$versionHash = md5('not installed');
		}

		// Add the js files
		// TODO: remove deprecated OC_Util injection
		$jsFiles = self::findJavascriptFiles(array_merge(\OC_Util::$scripts, Util::getScripts()));
		$this->assign('jsfiles', []);
		if ($this->config->getSystemValueBool('installed', false) && $renderAs != TemplateResponse::RENDER_AS_ERROR) {
			// this is on purpose outside of the if statement below so that the initial state is prefilled (done in the getConfig() call)
			// see https://github.com/nextcloud/server/pull/22636 for details
			$jsConfigHelper = new JSConfigHelper(
				\OCP\Server::get(ServerVersion::class),
				\OCP\Util::getL10N('lib'),
				\OCP\Server::get(Defaults::class),
				$this->appManager,
				\OC::$server->getSession(),
				\OC::$server->getUserSession()->getUser(),
				$this->config,
				\OC::$server->getGroupManager(),
				\OC::$server->get(IniGetWrapper::class),
				\OC::$server->getURLGenerator(),
				\OC::$server->get(CapabilitiesManager::class),
				\OCP\Server::get(IInitialStateService::class),
				\OCP\Server::get(IProvider::class),
				\OCP\Server::get(FilenameValidator::class),
			);
			$config = $jsConfigHelper->getConfig();
			if (\OC::$server->getContentSecurityPolicyNonceManager()->browserSupportsCspV3()) {
				$this->assign('inline_ocjs', $config);
			} else {
				$this->append('jsfiles', \OC::$server->getURLGenerator()->linkToRoute('core.OCJS.getConfig', ['v' => self::$versionHash]));
			}
		}
		foreach ($jsFiles as $info) {
			$web = $info[1];
			$file = $info[2];
			$this->append('jsfiles', $web . '/' . $file . $this->getVersionHashSuffix());
		}

		try {
			$pathInfo = \OC::$server->getRequest()->getPathInfo();
		} catch (\Exception $e) {
			$pathInfo = '';
		}

		// Do not initialise scss appdata until we have a fully installed instance
		// Do not load scss for update, errors, installation or login page
		if (\OC::$server->getSystemConfig()->getValue('installed', false)
			&& !\OCP\Util::needUpgrade()
			&& $pathInfo !== ''
			&& !preg_match('/^\/login/', $pathInfo)
			&& $renderAs !== TemplateResponse::RENDER_AS_ERROR
		) {
			$cssFiles = self::findStylesheetFiles(\OC_Util::$styles);
		} else {
			// If we ignore the scss compiler,
			// we need to load the guest css fallback
			\OC_Util::addStyle('guest');
			$cssFiles = self::findStylesheetFiles(\OC_Util::$styles, false);
		}

		$this->assign('cssfiles', []);
		$this->assign('printcssfiles', []);
		$this->initialState->provideInitialState('core', 'versionHash', self::$versionHash);
		foreach ($cssFiles as $info) {
			$web = $info[1];
			$file = $info[2];

			if (str_ends_with($file, 'print.css')) {
				$this->append('printcssfiles', $web . '/' . $file . $this->getVersionHashSuffix());
			} else {
				$suffix = $this->getVersionHashSuffix($web, $file);

				if (!str_contains($file, '?v=')) {
					$this->append('cssfiles', $web . '/' . $file . $suffix);
				} else {
					$this->append('cssfiles', $web . '/' . $file . '-' . substr($suffix, 3));
				}
			}
		}

		$request = \OCP\Server::get(IRequest::class);
		if ($request->isUserAgent([Request::USER_AGENT_CLIENT_IOS, Request::USER_AGENT_SAFARI, Request::USER_AGENT_SAFARI_MOBILE])) {
			// Prevent auto zoom with iOS but still allow user zoom
			// On chrome (and others) this does not work (will also disable user zoom)
			$this->assign('viewport_maximum_scale', '1.0');
		}

		$this->assign('initialStates', $this->initialState->getInitialStates());

		$this->assign('id-app-content', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-content' : '#content');
		$this->assign('id-app-navigation', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-navigation' : null);
	}

	protected function getVersionHashSuffix(string $path = '', string $file = ''): string {
		if ($this->config->getSystemValueBool('debug', false)) {
			// allows chrome workspace mapping in debug mode
			return '';
		}

		if ($this->config->getSystemValueBool('installed', false) === false) {
			// if not installed just return the version hash
			return '?v=' . self::$versionHash;
		}

		$hash = false;
		// Try the web-root first
		if ($path !== '') {
			$hash = $this->getVersionHashByPath($path);
		}
		// If not found try the file
		if ($hash === false && $file !== '') {
			$hash = $this->getVersionHashByPath($file);
		}
		// As a last resort we use the server version hash
		if ($hash === false) {
			$hash = self::$versionHash;
		}

		// The theming app is force-enabled thus the cache buster is always available
		$themingSuffix = '-' . $this->config->getAppValue('theming', 'cachebuster', '0');

		return '?v=' . $hash . $themingSuffix;
	}

	private function getVersionHashByPath(string $path): string|false {
		if (array_key_exists($path, self::$cacheBusterCache) === false) {
			// Not yet cached, so lets find the cache buster string
			$appId = $this->getAppNamefromPath($path);
			if ($appId === false || $appId === null) {
				// No app Id could be guessed
				return false;
			}

			if ($appId === 'core') {
				// core is not a real app but the server itself
				$hash = self::$versionHash;
			} else {
				$appVersion = $this->appManager->getAppVersion($appId);
				// For shipped apps the app version is not a single source of truth, we rather also need to consider the Nextcloud version
				if ($this->appManager->isShipped($appId)) {
					$appVersion .= '-' . self::$versionHash;
				}

				$hash = substr(md5($appVersion), 0, 8);
			}
			self::$cacheBusterCache[$path] = $hash;
		}

		return self::$cacheBusterCache[$path];
	}

	/**
	 * @param array $styles
	 * @return array
	 */
	public static function findStylesheetFiles($styles, $compileScss = true) {
		if (!self::$cssLocator) {
			self::$cssLocator = \OCP\Server::get(CSSResourceLocator::class);
		}
		self::$cssLocator->find($styles);
		return self::$cssLocator->getResources();
	}

	/**
	 * @param string $path
	 * @return string|false
	 */
	public function getAppNamefromPath($path) {
		if ($path !== '' && is_string($path)) {
			$pathParts = explode('/', $path);
			if ($pathParts[0] === 'css') {
				// This is a scss request
				return $pathParts[1];
			} elseif ($pathParts[0] === 'core') {
				return 'core';
			}
			return end($pathParts);
		}
		return false;
	}

	/**
	 * @param array $scripts
	 * @return array
	 */
	public static function findJavascriptFiles($scripts) {
		if (!self::$jsLocator) {
			self::$jsLocator = \OCP\Server::get(JSResourceLocator::class);
		}
		self::$jsLocator->find($scripts);
		return self::$jsLocator->getResources();
	}

	/**
	 * Converts the absolute file path to a relative path from \OC::$SERVERROOT
	 * @param string $filePath Absolute path
	 * @return string Relative path
	 * @throws \Exception If $filePath is not under \OC::$SERVERROOT
	 */
	public static function convertToRelativePath($filePath) {
		$relativePath = explode(\OC::$SERVERROOT, $filePath);
		if (count($relativePath) !== 2) {
			throw new \Exception('$filePath is not under the \OC::$SERVERROOT');
		}

		return $relativePath[1];
	}
}
