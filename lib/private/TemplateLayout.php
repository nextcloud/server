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
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;

class TemplateLayout extends \OC_Template {
	private static $versionHash = '';

	/** @var CSSResourceLocator|null */
	public static $cssLocator = null;

	/** @var JSResourceLocator|null */
	public static $jsLocator = null;

	private IConfig $config;
	private IRequest $request;
	private IAppManager $appManager;
	private InitialStateService $initialState;
	private INavigationManager $navigationManager;

	/**
	 * @param string $renderAs
	 * @param string $appId application id
	 */
	public function __construct(
		$renderAs,
		private string $appId = '',
	) {
		$this->config = \OCP\Server::get(IConfig::class);
		$this->request = \OCP\Server::get(IRequest::class);
		$this->appManager = \OCP\Server::get(IAppManager::class);
		$this->initialState = \OCP\Server::get(InitialStateService::class);
		$this->navigationManager = \OCP\Server::get(INavigationManager::class);

		// Decide which page we show
		switch ($renderAs) {
			case TemplateResponse::RENDER_AS_ERROR:
				parent::__construct('core', 'layout.guest', '', false);
				$this->setupLayoutError();
				break;
			case TemplateResponse::RENDER_AS_GUEST:
				parent::__construct('core', 'layout.guest');
				$this->setupLayoutGuest();
				break;
			case TemplateResponse::RENDER_AS_PUBLIC:
				parent::__construct('core', 'layout.public');
				$this->setupLayoutPublic();
				break;
			case TemplateResponse::RENDER_AS_USER:
				parent::__construct('core', 'layout.user');
				$this->setupLayoutUser();
				break;
			case TemplateResponse::RENDER_AS_BASE:
			case TemplateResponse::RENDER_AS_BLANK:
			default:
				parent::__construct('core', 'layout.base');
		}

		// Setup version hash
		if ($this->config->getSystemValueBool('installed', false)) {
			if (empty(self::$versionHash)) {
				$serverVersion = \OCP\Server::get(ServerVersion::class);
				$v = \OC_App::getAppVersions();
				$v['core'] = implode('.', $serverVersion->getVersion());
				self::$versionHash = substr(md5(implode(',', $v)), 0, 8);
			}
		} else {
			self::$versionHash = md5('not installed');
		}
		$this->initialState->provideInitialState('core', 'versionHash', self::$versionHash);

		// Setup common parameters
		$this->assign('appid', $appId);
		$this->assign('id-app-content', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-content' : '#content');
		$this->assign('id-app-navigation', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-navigation' : null);

		// Send the language, locale, and direction to our layouts
		$this->setupLanguageInformation();

		// Prevent auto zoom with iOS but still allow user zoom
		if ($this->request->isUserAgent([Request::USER_AGENT_CLIENT_IOS, Request::USER_AGENT_SAFARI, Request::USER_AGENT_SAFARI_MOBILE])) {
			// On chrome (and others) this does not work (will also disable user zoom)
			$this->assign('viewport_maximum_scale', '1.0');
		}

		// Add fallback theming variables if theming is disabled
		if ($renderAs !== TemplateResponse::RENDER_AS_USER) {
			// TODO cache generated default theme if enabled for fallback if server is erroring ?
			Util::addStyle('theming', 'default');
		}

		// Add the CSS files
		$this->setupCssFiles();

		// Add the JS config
		if ($this->config->getSystemValueBool('installed', false) && $renderAs !== TemplateResponse::RENDER_AS_ERROR) {
			$this->setupJsConfig();
		}

		// Add the JavaScript files
		$this->setupJavaScriptFiles();

		// Add all initial states
		$this->assign('initialStates', $this->initialState->getInitialStates());
	}

	/**
	 * Add all registered JavaScript files to the response
	 */
	private function setupJavaScriptFiles(): void {
		$this->assign('jsfiles', []);
		// TODO: remove deprecated OC_Util injection
		$jsFiles = self::findJavascriptFiles(array_merge(\OC_Util::$scripts, Util::getScripts()));
		foreach ($jsFiles as $info) {
			$web = $info[1];
			$file = $info[2];
			$this->append('jsfiles', $web . '/' . $file . $this->getVersionHashSuffix());
		}
	}

	/**
	 * Add the JS initial-config to the response
	 */
	private function setupJsConfig(): void {
		$jsConfigHelper = new JSConfigHelper(
			\OCP\Server::get(ServerVersion::class),
			\OCP\Util::getL10N('lib'),
			\OCP\Server::get(Defaults::class),
			$this->appManager,
			\OCP\Server::get(ISession::class),
			\OCP\Server::get(IUserSession::class)->getUser(),
			$this->config,
			\OCP\Server::get(IGroupManager::class),
			\OCP\Server::get(IniGetWrapper::class),
			\OCP\Server::get(IURLGenerator::class),
			\OCP\Server::get(CapabilitiesManager::class),
			$this->initialState,
			\OCP\Server::get(IProvider::class),
			\OCP\Server::get(FilenameValidator::class),
		);
		$config = $jsConfigHelper->getConfig();
		$this->assign('inline_ocjs', $config);
	}

	/**
	 * Add all registered CSS files
	 */
	private function setupCssFiles(): void {
		$this->assign('cssfiles', []);
		$this->assign('printcssfiles', []);

		// Try to get path info
		try {
			$pathInfo = $this->request->getPathInfo();
		} catch (\Exception) {
			$pathInfo = '';
		}

		// Add the guest styles as fallback if needed
		if ($this->config->getSystemValueBool('installed', false) === false
			|| \OCP\Util::needUpgrade()
			|| $pathInfo === ''
			|| preg_match('/^\/login/', $pathInfo)
		) {
			\OC_Util::addStyle('guest');
		}

		// Find all CSS files
		$cssFiles = self::findStylesheetFiles(\OC_Util::$styles);
		// Add CSS files with version hash (cache buster)
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
	}

	/**
	 * Setup user information on the template parameters
	 *
	 * @param bool $anonymous If set no user information will be set (same as if no user would have been logged-in)
	 */
	private function setupUserInformation(bool $anonymous = false): void {
		$user = $anonymous
			? null
			: \OCP\Server::get(IUserSession::class)->getUser();
		$userUid = $user?->getUID() ?? false;
		$userDisplayName = $user?->getDisplayName() ?? false;

		$this->assign('user_displayname', $userDisplayName);
		$this->assign('user_uid', $userUid);

		if ($user === null) {
			$this->assign('userAvatarSet', false);
			$this->assign('userStatus', false);
		} else {
			$this->assign('userAvatarSet', true);
			$this->assign('userAvatarVersion', $this->config->getUserValue($userUid, 'avatar', 'version', 0));
		}
	}

	/**
	 * Setup language and locale information on the template response
	 */
	private function setupLanguageInformation(): void {
		$l10n = \OCP\Server::get(IFactory::class);
		$lang = $l10n->findLanguage();
		$locale = $l10n->findLocale($lang);
		$direction = $l10n->getLanguageDirection($lang);
		// Make the language BCP47 compatible
		$lang = str_replace('_', '-', $lang);
		$this->assign('language', $lang);
		$this->assign('locale', $locale);
		$this->assign('direction', $direction);
	}

	/**
	 * Helper function to setup the layout for `renderAs = TemplateResponse::RENDER_AS_ERROR`
	 */
	private function setupLayoutError(): void {
		$this->assign('bodyid', 'body-login');
		$this->setupUserInformation(true);
		// Handle error always as guest page so we also need the guest styles
		\OC_Util::addStyle('guest');
	}

	/**
	 * Helper function to setup the layout for `renderAs = TemplateResponse::RENDER_AS_GUEST`
	 */
	private function setupLayoutGuest(): void {
		\OC_Util::addStyle('guest');
		$this->assign('bodyid', 'body-login');
		$this->setupUserInformation();
	}

	/**
	 * Helper function to setup the layout for `renderAs = TemplateResponse::RENDER_AS_PUBLIC`
	 */
	private function setupLayoutPublic(): void {
		$this->setupUserInformation(true);
		$this->assign('bodyid', 'body-public');

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
	}

	/**
	 * Helper function to setup the layout for `renderAs = TemplateResponse::RENDER_AS_USER`
	 */
	private function setupLayoutUser(): void {
		$this->setupUserInformation();
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
			$themesService = \OCP\Server::get(\OCA\Theming\Service\ThemesService::class);
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
	}

	protected function getVersionHashSuffix(?string $path = null, ?string $file = null) {
		if ($this->config->getSystemValueBool('debug', false)) {
			// allows chrome workspace mapping in debug mode
			return '';
		}
		$themingSuffix = '';
		$v = [];

		if ($this->config->getSystemValueBool('installed', false)) {
			if ($this->appManager->isInstalled('theming')) {
				$themingSuffix = '-' . $this->config->getAppValue('theming', 'cachebuster', '0');
			}
			$v = \OC_App::getAppVersions();
		}

		// Try the webroot path for a match
		if ($path !== null && $path !== '') {
			$appName = $this->getAppNameFromPath($path);
			if (array_key_exists($appName, $v)) {
				$appVersion = $v[$appName];
				return '?v=' . substr(md5($appVersion), 0, 8) . $themingSuffix;
			}
		}
		// fallback to the file path instead
		if ($file !== null && $file !== '') {
			$appName = $this->getAppNameFromPath($file);
			if (array_key_exists($appName, $v)) {
				$appVersion = $v[$appName];
				return '?v=' . substr(md5($appVersion), 0, 8) . $themingSuffix;
			}
		}

		return '?v=' . self::$versionHash . $themingSuffix;
	}

	/**
	 * @param array $styles
	 * @return array
	 */
	public static function findStylesheetFiles($styles) {
		if (!self::$cssLocator) {
			self::$cssLocator = \OCP\Server::get(CSSResourceLocator::class);
		}
		self::$cssLocator->find($styles);
		return self::$cssLocator->getResources();
	}

	/**
	 * @param string $path
	 * @return string|boolean
	 */
	public function getAppNameFromPath($path) {
		if ($path !== '' && is_string($path)) {
			$pathParts = explode('/', $path);
			if ($pathParts[0] === 'css') {
				// This is a scss request
				return $pathParts[1];
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
