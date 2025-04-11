<?php

declare(strict_types=1);

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
use OCP\Server;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use OCP\Template\ITemplate;
use OCP\Template\ITemplateManager;
use OCP\Util;

class TemplateLayout {
	private static string $versionHash = '';
	/** @var string[] */
	private static array $cacheBusterCache = [];

	public static ?CSSResourceLocator $cssLocator = null;
	public static ?JSResourceLocator $jsLocator = null;

	public function __construct(
		private IConfig $config,
		private IAppManager $appManager,
		private InitialStateService $initialState,
		private INavigationManager $navigationManager,
		private ITemplateManager $templateManager,
		private ServerVersion $serverVersion,
	) {
	}

	public function getPageTemplate(string $renderAs, string $appId): ITemplate {
		// Add fallback theming variables if not rendered as user
		if ($renderAs !== TemplateResponse::RENDER_AS_USER) {
			// TODO cache generated default theme if enabled for fallback if server is erroring ?
			Util::addStyle('theming', 'default');
		}

		// Decide which page we show
		switch ($renderAs) {
			case TemplateResponse::RENDER_AS_USER:
				$page = $this->templateManager->getTemplate('core', 'layout.user');
				if (in_array(\OC_App::getCurrentApp(), ['settings','admin', 'help']) !== false) {
					$page->assign('bodyid', 'body-settings');
				} else {
					$page->assign('bodyid', 'body-user');
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

				// Set logo link target
				$logoUrl = $this->config->getSystemValueString('logo_url', '');
				$page->assign('logoUrl', $logoUrl);

				// Set default entry name
				$defaultEntryId = $this->navigationManager->getDefaultEntryIdForUser();
				$defaultEntry = $this->navigationManager->get($defaultEntryId);
				$page->assign('defaultAppName', $defaultEntry['name'] ?? '');

				// Add navigation entry
				$page->assign('application', '');
				$page->assign('appid', $appId);

				$navigation = $this->navigationManager->getAll();
				$page->assign('navigation', $navigation);
				$settingsNavigation = $this->navigationManager->getAll('settings');
				$this->initialState->provideInitialState('core', 'settingsNavEntries', $settingsNavigation);

				foreach ($navigation as $entry) {
					if ($entry['active']) {
						$page->assign('application', $entry['name']);
						break;
					}
				}

				foreach ($settingsNavigation as $entry) {
					if ($entry['active']) {
						$page->assign('application', $entry['name']);
						break;
					}
				}

				$user = Server::get(IUserSession::class)->getUser();

				if ($user === null) {
					$page->assign('user_uid', false);
					$page->assign('user_displayname', false);
					$page->assign('userAvatarSet', false);
					$page->assign('userStatus', false);
				} else {
					$page->assign('user_uid', $user->getUID());
					$page->assign('user_displayname', $user->getDisplayName());
					$page->assign('userAvatarSet', true);
					$page->assign('userAvatarVersion', $this->config->getUserValue($user->getUID(), 'avatar', 'version', 0));
				}
				break;
			case TemplateResponse::RENDER_AS_ERROR:
				$page = $this->templateManager->getTemplate('core', 'layout.guest', '', false);
				$page->assign('bodyid', 'body-login');
				$page->assign('user_displayname', '');
				$page->assign('user_uid', '');
				break;
			case TemplateResponse::RENDER_AS_GUEST:
				$page = $this->templateManager->getTemplate('core', 'layout.guest');
				Util::addStyle('guest');
				$page->assign('bodyid', 'body-login');

				$userDisplayName = false;
				$user = Server::get(IUserSession::class)->getUser();
				if ($user) {
					$userDisplayName = $user->getDisplayName();
				}

				$page->assign('enabledThemes', []);
				if ($this->appManager->isEnabledForUser('theming') && class_exists('\OCA\Theming\Service\ThemesService')) {
					$themesService = Server::get(\OCA\Theming\Service\ThemesService::class);
					$page->assign('enabledThemes', $themesService->getEnabledThemes());
				}

				$page->assign('user_displayname', $userDisplayName);
				$page->assign('user_uid', \OC_User::getUser());
				break;
			case TemplateResponse::RENDER_AS_PUBLIC:
				$page = $this->templateManager->getTemplate('core', 'layout.public');
				$page->assign('appid', $appId);
				$page->assign('bodyid', 'body-public');

				// Set logo link target
				$logoUrl = $this->config->getSystemValueString('logo_url', '');
				$page->assign('logoUrl', $logoUrl);

				$subscription = Server::get(IRegistry::class);
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
					$urlGenerator = Server::get(IURLGenerator::class);
					$signUpLink = $urlGenerator->getAbsoluteURL('/index.php/apps/registration/');
				}

				$page->assign('showSimpleSignUpLink', $showSimpleSignup);
				$page->assign('signUpLink', $signUpLink);
				break;
			default:
				$page = $this->templateManager->getTemplate('core', 'layout.base');
				break;
		}
		// Send the language, locale, and direction to our layouts
		$l10nFactory = Server::get(IFactory::class);
		$lang = $l10nFactory->findLanguage();
		$locale = $l10nFactory->findLocale($lang);
		$direction = $l10nFactory->getLanguageDirection($lang);

		$lang = str_replace('_', '-', $lang);
		$page->assign('language', $lang);
		$page->assign('locale', $locale);
		$page->assign('direction', $direction);

		// Set body data-theme
		$themesService = Server::get(\OCA\Theming\Service\ThemesService::class);
		$page->assign('enabledThemes', $themesService->getEnabledThemes());

		if ($this->config->getSystemValueBool('installed', false)) {
			if (empty(self::$versionHash)) {
				$v = $this->appManager->getAppInstalledVersions();
				$v['core'] = implode('.', $this->serverVersion->getVersion());
				self::$versionHash = substr(md5(implode(',', $v)), 0, 8);
			}
		} else {
			self::$versionHash = md5('not installed');
		}

		// Add the js files
		$jsFiles = self::findJavascriptFiles(Util::getScripts());
		$page->assign('jsfiles', []);
		if ($this->config->getSystemValueBool('installed', false) && $renderAs != TemplateResponse::RENDER_AS_ERROR) {
			// this is on purpose outside of the if statement below so that the initial state is prefilled (done in the getConfig() call)
			// see https://github.com/nextcloud/server/pull/22636 for details
			$jsConfigHelper = new JSConfigHelper(
				$this->serverVersion,
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
				$page->assign('inline_ocjs', $config);
			} else {
				$page->append('jsfiles', \OC::$server->getURLGenerator()->linkToRoute('core.OCJS.getConfig', ['v' => self::$versionHash]));
			}
		}
		foreach ($jsFiles as $info) {
			$web = $info[1];
			$file = $info[2];
			$page->append('jsfiles', $web . '/' . $file . $this->getVersionHashSuffix());
		}

		$request = \OCP\Server::get(IRequest::class);

		try {
			$pathInfo = $request->getPathInfo();
		} catch (\Exception $e) {
			$pathInfo = '';
		}

		// Do not initialise scss appdata until we have a fully installed instance
		// Do not load scss for update, errors, installation or login page
		if ($this->config->getSystemValueBool('installed', false)
			&& !\OCP\Util::needUpgrade()
			&& $pathInfo !== ''
			&& !preg_match('/^\/login/', $pathInfo)
			&& $renderAs !== TemplateResponse::RENDER_AS_ERROR
		) {
			$cssFiles = self::findStylesheetFiles(\OC_Util::$styles);
		} else {
			// If we ignore the scss compiler,
			// we need to load the guest css fallback
			Util::addStyle('guest');
			$cssFiles = self::findStylesheetFiles(\OC_Util::$styles);
		}

		$page->assign('cssfiles', []);
		$page->assign('printcssfiles', []);
		$this->initialState->provideInitialState('core', 'versionHash', self::$versionHash);
		foreach ($cssFiles as $info) {
			$web = $info[1];
			$file = $info[2];

			if (str_ends_with($file, 'print.css')) {
				$page->append('printcssfiles', $web . '/' . $file . $this->getVersionHashSuffix());
			} else {
				$suffix = $this->getVersionHashSuffix($web, $file);

				if (!str_contains($file, '?v=')) {
					$page->append('cssfiles', $web . '/' . $file . $suffix);
				} else {
					$page->append('cssfiles', $web . '/' . $file . '-' . substr($suffix, 3));
				}
			}
		}

		if ($request->isUserAgent([Request::USER_AGENT_CLIENT_IOS, Request::USER_AGENT_SAFARI, Request::USER_AGENT_SAFARI_MOBILE])) {
			// Prevent auto zoom with iOS but still allow user zoom
			// On chrome (and others) this does not work (will also disable user zoom)
			$page->assign('viewport_maximum_scale', '1.0');
		}

		$page->assign('initialStates', $this->initialState->getInitialStates());

		$page->assign('id-app-content', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-content' : '#content');
		$page->assign('id-app-navigation', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-navigation' : null);

		return $page;
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
			if ($appId === false) {
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

	public static function findStylesheetFiles(array $styles): array {
		if (!self::$cssLocator) {
			self::$cssLocator = \OCP\Server::get(CSSResourceLocator::class);
		}
		self::$cssLocator->find($styles);
		return self::$cssLocator->getResources();
	}

	public function getAppNamefromPath(string $path): string|false {
		if ($path !== '') {
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

	public static function findJavascriptFiles(array $scripts): array {
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
	public static function convertToRelativePath(string $filePath) {
		$relativePath = explode(\OC::$SERVERROOT, $filePath);
		if (count($relativePath) !== 2) {
			throw new \Exception('$filePath is not under the \OC::$SERVERROOT');
		}

		return $relativePath[1];
	}
}
