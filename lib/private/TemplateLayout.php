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
use OC\Core\AppInfo\Application;
use OC\Core\AppInfo\ConfigLexicon;
use OC\Files\FilenameValidator;
use OC\Search\SearchQuery;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\Template\CSSResourceLocator;
use OC\Template\JSConfigHelper;
use OC\Template\JSResourceLocator;
use OCA\Theming\Service\ThemesService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IInitialStateService;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Support\Subscription\IRegistry;
use OCP\Template\ITemplate;
use OCP\Template\ITemplateManager;
use OCP\Util;

/**
 * Builds and populates page layout templates for the different render modes
 * (user, guest, public, error, base), including navigation, initial state,
 * asset lists, language metadata, and cache-busting version suffixes.
 */
class TemplateLayout {
	private string $versionHash = '';
	/** @var string[] */
	private array $cacheBusterCache = [];

	public ?CSSResourceLocator $cssLocator = null;
	public ?JSResourceLocator $jsLocator = null;

	public function __construct(
		private IConfig $config,
		private readonly IAppConfig $appConfig,
		private IAppManager $appManager,
		private InitialStateService $initialState,
		private INavigationManager $navigationManager,
		private ITemplateManager $templateManager,
		private ServerVersion $serverVersion,
		private IRequest $request,
	) {
	}

	/**
	 * Build the layout template for the requested render mode and populate it with
	 * common view data such as navigation, localization, initial state, user data,
	 * and versioned JS/CSS assets.
	 *
	 * @param string $renderAs One of the TemplateResponse::RENDER_AS_* constants
	 * @param string $appId Active app identifier used for navigation and public layout state
	 * @return ITemplate Prepared layout template
	 */
	public function getPageTemplate(string $renderAs, string $appId): ITemplate {
		// Add fallback theming variables if not rendered as user
		if ($renderAs !== TemplateResponse::RENDER_AS_USER) {
			// TODO cache generated default theme if enabled for fallback if server is erroring ?
			Util::addStyle('theming', 'default');
		}

		// Select the base layout template for the requested render mode.
		switch ($renderAs) {
			case TemplateResponse::RENDER_AS_USER:
				$template = $this->templateManager->getTemplate('core', 'layout.user');
				$pathInfo = $this->request->getPathInfo();
				if ($pathInfo !== false && str_starts_with($pathInfo, '/settings/')) {
					$template->assign('bodyid', 'body-settings');
				} else {
					$template->assign('bodyid', 'body-user');
				}

				$this->initialState->provideInitialState('core', 'active-app', $this->navigationManager->getActiveEntry());
				$this->initialState->provideInitialState('core', 'apps', array_values($this->navigationManager->getAll()));

				$this->initialState->provideInitialState('unified-search', 'min-search-length', $this->appConfig->getValueInt(Application::APP_ID, ConfigLexicon::UNIFIED_SEARCH_MIN_SEARCH_LENGTH));
				if ($this->config->getSystemValueBool('unified_search.enabled', false) || !$this->config->getSystemValueBool('enable_non-accessible_features', true)) {
					$this->initialState->provideInitialState('unified-search', 'limit-default', (int)$this->config->getAppValue('core', 'unified-search.limit-default', (string)SearchQuery::LIMIT_DEFAULT));
					$this->initialState->provideInitialState('unified-search', 'live-search', $this->config->getAppValue('core', 'unified-search.live-search', 'yes') === 'yes');
					Util::addScript('core', 'legacy-unified-search', 'core');
				} else {
					Util::addScript('core', 'unified-search', 'core');
				}

				// Set logo link target
				$logoUrl = $this->config->getSystemValueString('logo_url', '');
				$template->assign('logoUrl', $logoUrl);

				// Set default entry name
				$defaultNavigationEntryId = $this->navigationManager->getDefaultEntryIdForUser();
				$defaultNavigationEntry = $this->navigationManager->get($defaultNavigationEntryId);
				$template->assign('defaultAppName', $defaultNavigationEntry['name'] ?? '');

				// Add navigation entry
				$template->assign('application', '');
				$template->assign('appid', $appId);

				$mainNavigation = $this->navigationManager->getAll();
				$template->assign('navigation', $mainNavigation);
				$settingsNavigation = $this->navigationManager->getAll('settings');
				$this->initialState->provideInitialState('core', 'settingsNavEntries', $settingsNavigation);

				foreach ($mainNavigation as $entry) {
					if ($entry['active']) {
						$template->assign('application', $entry['name']);
						break;
					}
				}

				foreach ($settingsNavigation as $entry) {
					if ($entry['active']) {
						$template->assign('application', $entry['name']);
						break;
					}
				}

				$user = Server::get(IUserSession::class)->getUser();

				if ($user === null) {
					$template->assign('user_uid', false);
					$template->assign('user_displayname', false);
					$template->assign('userAvatarSet', false);
					$template->assign('userStatus', false);
				} else {
					$template->assign('user_uid', $user->getUID());
					$template->assign('user_displayname', $user->getDisplayName());
					$template->assign('userAvatarSet', true);
					$template->assign('userAvatarVersion', $this->config->getUserValue($user->getUID(), 'avatar', 'version', 0));
				}
				break;
			case TemplateResponse::RENDER_AS_ERROR:
				$template = $this->templateManager->getTemplate('core', 'layout.guest', '', false);
				$template->assign('bodyid', 'body-login');
				$template->assign('user_displayname', '');
				$template->assign('user_uid', '');
				break;
			case TemplateResponse::RENDER_AS_GUEST:
				$template = $this->templateManager->getTemplate('core', 'layout.guest');
				Util::addStyle('guest');
				$template->assign('bodyid', 'body-login');

				$userDisplayName = false;
				$user = Server::get(IUserSession::class)->getUser();
				if ($user) {
					$userDisplayName = $user->getDisplayName();
				}

				$template->assign('user_displayname', $userDisplayName);
				$template->assign('user_uid', \OC_User::getUser());
				break;
			case TemplateResponse::RENDER_AS_PUBLIC:
				$template = $this->templateManager->getTemplate('core', 'layout.public');
				$template->assign('appid', $appId);
				$template->assign('bodyid', 'body-public');

				$currentAppNavigationEntry = $this->navigationManager->get($appId);
				$this->initialState->provideInitialState('core', 'apps', $currentAppNavigationEntry === null ? [] : [$currentAppNavigationEntry]);

				// Set logo link target
				$logoUrl = $this->config->getSystemValueString('logo_url', '');
				$template->assign('logoUrl', $logoUrl);

				$subscriptionRegistry = Server::get(IRegistry::class);
				$showSimpleSignup = $this->config->getSystemValueBool('simpleSignUpLink.shown', true);
				if ($showSimpleSignup && $subscriptionRegistry->delegateHasValidSubscription()) {
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

				$template->assign('showSimpleSignUpLink', $showSimpleSignup);
				$template->assign('signUpLink', $signUpLink);
				break;
			default:
				$template = $this->templateManager->getTemplate('core', 'layout.base');
				break;
		}

		// Expose localization metadata to the selected layout.
		$l10nFactory = Server::get(IFactory::class);
		$lang = $l10nFactory->findLanguage();
		$locale = $l10nFactory->findLocale($lang);
		$direction = $l10nFactory->getLanguageDirection($lang);

		$lang = str_replace('_', '-', $lang);
		$template->assign('language', $lang);
		$template->assign('locale', $locale);
		$template->assign('direction', $direction);

		// Expose enabled themes for body/theme-related rendering.
		try {
			$themesService = Server::get(ThemesService::class);
		} catch (\Exception) {
			$themesService = null;
		}
		$template->assign('enabledThemes', $themesService?->getEnabledThemes() ?? []);

		if ($this->config->getSystemValueBool('installed', false)) {
			if (empty($this->versionHash)) {
				$v = $this->appManager->getAppInstalledVersions(true);
				$v['core'] = implode('.', $this->serverVersion->getVersion());
				$this->versionHash = substr(md5(implode(',', $v)), 0, 8);
			}
		} else {
			$this->versionHash = md5('not installed');
		}

		// Resolve and append JavaScript assets.
		$jsFiles = $this->findJavascriptFiles(Util::getScripts());
		$template->assign('jsfiles', []);
		if ($this->config->getSystemValueBool('installed', false) && $renderAs !== TemplateResponse::RENDER_AS_ERROR) {
			// Intentionally build JS config before deciding how to deliver it so the
			// initial state is populated as a side effect of getConfig().
			// See PR #22636 for historical context.
			$jsConfigHelper = new JSConfigHelper(
				$this->serverVersion,
				Util::getL10N('lib'),
				Server::get(Defaults::class),
				$this->appManager,
				Server::get(ISession::class),
				Server::get(IUserSession::class)->getUser(),
				$this->config,
				$this->appConfig,
				Server::get(IGroupManager::class),
				Server::get(IniGetWrapper::class),
				Server::get(IURLGenerator::class),
				Server::get(CapabilitiesManager::class),
				Server::get(IInitialStateService::class),
				Server::get(IProvider::class),
				Server::get(FilenameValidator::class),
			);
			$config = $jsConfigHelper->getConfig();
			if (Server::get(ContentSecurityPolicyNonceManager::class)->browserSupportsCspV3()) {
				$template->assign('inline_ocjs', $config);
			} else {
				$template->append('jsfiles', Server::get(IURLGenerator::class)->linkToRoute('core.OCJS.getConfig', ['v' => $this->versionHash]));
			}
		}
		/** @var array{0:string,1:string,2:string} $resourceInfo */
		foreach ($jsFiles as $info) {
			[$absolutePath, $webPath, $resourcePath] = $info;
			$template->append('jsfiles', $webPath . '/' . $resourcePath . $this->getVersionHashSuffix());
		}

		try {
			$pathInfo = $this->request->getPathInfo();
		} catch (\Exception $e) {
			$pathInfo = '';
		}

		// Only use compiled SCSS assets on fully installed, non-upgrade, non-error,
		// non-login requests. Fall back to guest styling otherwise.
		if ($this->config->getSystemValueBool('installed', false)
			&& !Util::needUpgrade()
			&& $pathInfo !== ''
			&& !preg_match('/^\/login/', $pathInfo)
			&& $renderAs !== TemplateResponse::RENDER_AS_ERROR
		) {
			$cssFiles = $this->findStylesheetFiles(\OC_Util::$styles);
		} else {
			// If we ignore the scss compiler,
			// we need to load the guest css fallback
			Util::addStyle('guest');
			$cssFiles = $this->findStylesheetFiles(\OC_Util::$styles);
		}

		$template->assign('cssfiles', []);
		$template->assign('printcssfiles', []);
		$this->initialState->provideInitialState('core', 'versionHash', $this->versionHash);
		/** @var array{0:string,1:string,2:string} $resourceInfo */
		foreach ($cssFiles as $info) {
			[$absolutePath, $webPath, $resourcePath] = $info;

			if (str_ends_with($file, 'print.css')) {
				$template->append('printcssfiles', $webPath . '/' . $resourcePath . $this->getVersionHashSuffix());
			} else {
				$suffix = $this->getVersionHashSuffix($webPath, $resourcePath);

				if (!str_contains($file, '?v=')) {
					$template->append('cssfiles', $webPath . '/' . $resourcePath . $suffix);
				} else {
					$template->append('cssfiles', $webPath . '/' . $resourcePath . '-' . substr($suffix, 3));
				}
			}
		}

		if ($this->request->isUserAgent([Request::USER_AGENT_CLIENT_IOS, Request::USER_AGENT_SAFARI, Request::USER_AGENT_SAFARI_MOBILE])) {
			// Prevent auto zoom with iOS but still allow user zoom
			// On chrome (and others) this does not work (will also disable user zoom)
			$template->assign('viewport_maximum_scale', '1.0');
		}

		$template->assign('initialStates', $this->initialState->getInitialStates());

		$template->assign('id-app-content', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-content' : '#content');
		$template->assign('id-app-navigation', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-navigation' : null);

		return $template;
	}

	/**
	 * Build the cache-busting query suffix for a static resource.
	 *
	 * In non-debug mode this prefers a hash derived from the owning app/version
	 * when the app can be inferred from the resource path. If that fails, it falls
	 * back to the server-wide version hash. A theming cache-buster is always appended.
	 *
	 * @param string $path Resource web path hint
	 * @param string $file Resource file hint
	 * @return string Query suffix beginning with "?v=", or an empty string in debug mode
	 */
	protected function getVersionHashSuffix(string $resourcePathHint = '', string $file = ''): string {
		if ($this->config->getSystemValueBool('debug', false)) {
			// allows chrome workspace mapping in debug mode
			return '';
		}

		if ($this->config->getSystemValueBool('installed', false) === false) {
			// if not installed just return the version hash
			return '?v=' . $this->versionHash;
		}

		$hash = false;
		// Try the web-root first
		if ($path !== '') {
			$hash = $this->getVersionHashByPath($path);
		}
		// If no hash was derived from the web path, try the file hint.
		if ($hash === false && $file !== '') {
			$hash = $this->getVersionHashByPath($file);
		}
		// Fall back to the server-wide version hash.
		if ($hash === false) {
			$hash = $this->versionHash;
		}

		// The theming app is force-enabled thus the cache buster is always available
		$themingSuffix = '-' . $this->config->getAppValue('theming', 'cachebuster', '0');

		return '?v=' . $hash . $themingSuffix;
	}

	/**
	 * Resolve a cache-busting hash for a resource path by inferring its owning app.
	 *
	 * Returns false when no app can be inferred from the provided path.
	 *
	 * @param string $path Resource path used to infer the app name
	 * @return string|false
	 */
	private function getVersionHashByPath(string $path): string|false {
		if (array_key_exists($path, $this->cacheBusterCache) === false) {
			// Not cached yet; compute the resource cache-buster hash.
			$appId = $this->getAppNameFromPath($path);
			if ($appId === false) {
				// Unable to infer an owning app from the resource path.
				return false;
			}

			if ($appId === 'core') {
				// "core" maps to the server version hash rather than an app version.
				$hash = $this->versionHash;
			} else {
				$appVersion = $this->appManager->getAppVersion($appId);
				// For shipped apps the app version is not a single source of truth, we rather also need to consider the Nextcloud version
				if ($this->appManager->isShipped($appId)) {
					$appVersion .= '-' . $this->versionHash;
				}

				$hash = substr(md5($appVersion), 0, 8);
			}
			$this->cacheBusterCache[$path] = $hash;
		}

		return $this->cacheBusterCache[$path];
	}

	/**
	 * Resolve stylesheet resources via the CSS resource locator.
	 *
	 * @param array $styles Registered style definitions
	 * @return array<int, array{0:string, 1:string, 2:string}> Located stylesheet resources
	 */
	private function findStylesheetFiles(array $styles): array {
		if ($this->cssLocator === null) {
			$this->cssLocator = Server::get(CSSResourceLocator::class);
		}
		$this->cssLocator->find($styles);
		return $this->cssLocator->getResources();
	}

	/**
	 * Heuristically infer the app name from a resource path.
	 *
	 * Expected formats include:
	 * - "css/<app>/..."
	 * - "core/..."
	 * - other resource paths where the last path segment represents the app id
	 *
	 * @param string $path
	 * @return string|false Inferred app id, or false if it cannot be determined
	 */
	public function getAppNameFromPath(string $path): string|false {
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

	/**
	 * Resolve Javascript resources via the JS resource locator.
	 *
	 * @param array $styles Registered JS definitions
	 * @return array<int, array{0:string, 1:string, 2:string}> Located JS resources
	 */
	private function findJavascriptFiles(array $scripts): array {
		if ($this->jsLocator === null) {
			$this->jsLocator = Server::get(JSResourceLocator::class);
		}
		$this->jsLocator->find($scripts);
		return $this->jsLocator->getResources();
	}

	/**
	 * Convert an absolute file path into a path relative to \OC::$SERVERROOT.
	 *
	 * @param string $filePath Absolute path
	 * @return string Relative path
	 * @throws \Exception If the file path is not under \OC::$SERVERROOT
	 */
	public static function convertToRelativePath(string $filePath): string {
		$pathParts = explode(\OC::$SERVERROOT, $filePath);
		if (count($pathParts) !== 2) {
			throw new \Exception('$filePath is not under the \OC::$SERVERROOT');
		}

		return $pathParts[1];
	}
}
