<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Guillaume COMPAGNON <gcompagnon@outlook.com>
 * @author Hendrik Leppelsack <hendrik@leppelsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nils <git@to.nilsschnabel.de>
 * @author Remco Brenninkmeijer <requist1@starmail.nl>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use bantu\IniGetWrapper\IniGetWrapper;
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
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;

class TemplateLayout extends \OC_Template {
	private static $versionHash = '';

	/** @var CSSResourceLocator|null */
	public static $cssLocator = null;

	/** @var JSResourceLocator|null */
	public static $jsLocator = null;

	/** @var IConfig */
	private $config;

	/** @var IInitialStateService */
	private $initialState;

	/** @var INavigationManager */
	private $navigationManager;

	/**
	 * @param string $renderAs
	 * @param string $appId application id
	 */
	public function __construct($renderAs, $appId = '') {
		/** @var IConfig */
		$this->config = \OC::$server->get(IConfig::class);

		/** @var IInitialStateService */
		$this->initialState = \OC::$server->get(IInitialStateService::class);

		// Add fallback theming variables if theming is disabled
		if ($renderAs !== TemplateResponse::RENDER_AS_USER
			|| !\OC::$server->getAppManager()->isEnabledForUser('theming')) {
			// TODO cache generated default theme if enabled for fallback if server is erroring ?
			Util::addStyle('theming', 'default');
		}

		// Decide which page we show
		if ($renderAs === TemplateResponse::RENDER_AS_USER) {
			/** @var INavigationManager */
			$this->navigationManager = \OC::$server->get(INavigationManager::class);

			parent::__construct('core', 'layout.user');
			if (in_array(\OC_App::getCurrentApp(), ['settings','admin', 'help']) !== false) {
				$this->assign('bodyid', 'body-settings');
			} else {
				$this->assign('bodyid', 'body-user');
			}

			$this->initialState->provideInitialState('core', 'active-app', $this->navigationManager->getActiveEntry());
			$this->initialState->provideInitialState('core', 'apps', $this->navigationManager->getAll());

			if ($this->config->getSystemValueBool('unified_search.enabled', false) || !$this->config->getSystemValueBool('enable_non-accessible_features', true)) {
				$this->initialState->provideInitialState('unified-search', 'limit-default', (int)$this->config->getAppValue('core', 'unified-search.limit-default', (string)SearchQuery::LIMIT_DEFAULT));
				$this->initialState->provideInitialState('unified-search', 'min-search-length', (int)$this->config->getAppValue('core', 'unified-search.min-search-length', (string)1));
				$this->initialState->provideInitialState('unified-search', 'live-search', $this->config->getAppValue('core', 'unified-search.live-search', 'yes') === 'yes');
				Util::addScript('core', 'unified-search', 'core');
			} else {
				Util::addScript('core', 'global-search', 'core');
			}
			// Set body data-theme
			$this->assign('enabledThemes', []);
			if (\OC::$server->getAppManager()->isEnabledForUser('theming') && class_exists('\OCA\Theming\Service\ThemesService')) {
				/** @var \OCA\Theming\Service\ThemesService */
				$themesService = \OC::$server->get(\OCA\Theming\Service\ThemesService::class);
				$this->assign('enabledThemes', $themesService->getEnabledThemes());
			}

			// Set logo link target
			$logoUrl = $this->config->getSystemValueString('logo_url', '');
			$this->assign('logoUrl', $logoUrl);

			// Set default app name
			$defaultApp = \OC::$server->getAppManager()->getDefaultAppForUser();
			$defaultAppInfo = \OC::$server->getAppManager()->getAppInfo($defaultApp);
			$l10n = \OC::$server->getL10NFactory()->get($defaultApp);
			$this->assign('defaultAppName', $l10n->t($defaultAppInfo['name']));

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

			$appManager = \OCP\Server::get(IAppManager::class);
			if ($appManager->isEnabledForUser('registration')) {
				$urlGenerator = \OCP\Server::get(IURLGenerator::class);
				$signUpLink = $urlGenerator->getAbsoluteURL('/index.php/apps/registration/');
			}

			$this->assign('showSimpleSignUpLink', $showSimpleSignup);
			$this->assign('signUpLink', $signUpLink);
		} else {
			parent::__construct('core', 'layout.base');
		}
		// Send the language and the locale to our layouts
		$lang = \OC::$server->getL10NFactory()->findLanguage();
		$locale = \OC::$server->getL10NFactory()->findLocale($lang);

		$lang = str_replace('_', '-', $lang);
		$this->assign('language', $lang);
		$this->assign('locale', $locale);

		if (\OC::$server->getSystemConfig()->getValue('installed', false)) {
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
				\OC::$server->getL10N('lib'),
				\OCP\Server::get(Defaults::class),
				\OC::$server->getAppManager(),
				\OC::$server->getSession(),
				\OC::$server->getUserSession()->getUser(),
				$this->config,
				\OC::$server->getGroupManager(),
				\OC::$server->get(IniGetWrapper::class),
				\OC::$server->getURLGenerator(),
				\OC::$server->getCapabilitiesManager(),
				\OCP\Server::get(IInitialStateService::class)
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
			$this->append('jsfiles', $web.'/'.$file . $this->getVersionHashSuffix());
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
				$this->append('printcssfiles', $web.'/'.$file . $this->getVersionHashSuffix());
			} else {
				$suffix = $this->getVersionHashSuffix($web, $file);

				if (!str_contains($file, '?v=')) {
					$this->append('cssfiles', $web.'/'.$file . $suffix);
				} else {
					$this->append('cssfiles', $web.'/'.$file . '-' . substr($suffix, 3));
				}
			}
		}

		$this->assign('initialStates', $this->initialState->getInitialStates());

		$this->assign('id-app-content', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-content' : '#content');
		$this->assign('id-app-navigation', $renderAs === TemplateResponse::RENDER_AS_USER ? '#app-navigation' : null);
	}

	/**
	 * @param string $path
	 * @param string $file
	 * @return string
	 */
	protected function getVersionHashSuffix($path = false, $file = false) {
		if ($this->config->getSystemValueBool('debug', false)) {
			// allows chrome workspace mapping in debug mode
			return "";
		}
		$themingSuffix = '';
		$v = [];

		if ($this->config->getSystemValueBool('installed', false)) {
			if (\OC::$server->getAppManager()->isInstalled('theming')) {
				$themingSuffix = '-' . $this->config->getAppValue('theming', 'cachebuster', '0');
			}
			$v = \OC_App::getAppVersions();
		}

		// Try the webroot path for a match
		if ($path !== false && $path !== '') {
			$appName = $this->getAppNamefromPath($path);
			if (array_key_exists($appName, $v)) {
				$appVersion = $v[$appName];
				return '?v=' . substr(md5($appVersion), 0, 8) . $themingSuffix;
			}
		}
		// fallback to the file path instead
		if ($file !== false && $file !== '') {
			$appName = $this->getAppNamefromPath($file);
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
	public static function findStylesheetFiles($styles, $compileScss = true) {
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
	public function getAppNamefromPath($path) {
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
