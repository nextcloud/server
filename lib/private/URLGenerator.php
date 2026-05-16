<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\Route\Router;
use OCA\Theming\ThemingDefaults;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Server;
use Override;
use RuntimeException;

class URLGenerator implements IURLGenerator {
	private ?string $baseUrl = null;
	private ?IAppManager $appManager = null;
	private ?INavigationManager $navigationManager = null;

	public function __construct(
		private IConfig $config,
		public IUserSession $userSession,
		private ICacheFactory $cacheFactory,
		private IRequest $request,
		private Router $router,
	) {
	}

	private function getAppManager(): IAppManager {
		if ($this->appManager !== null) {
			return $this->appManager;
		}
		$this->appManager = Server::get(IAppManager::class);
		return $this->appManager;
	}

	private function getNavigationManager(): INavigationManager {
		if ($this->navigationManager !== null) {
			return $this->navigationManager;
		}
		$this->navigationManager = Server::get(INavigationManager::class);
		return $this->navigationManager;
	}

	/**
	 * Creates an url using a defined route
	 *
	 * @param string $routeName
	 * @param array $arguments args with param=>value, will be appended to the returned url
	 * @return string the url
	 *
	 * Returns a url to the given route.
	 */
	#[\Override]
	public function linkToRoute(string $routeName, array $arguments = []): string {
		return $this->router->generate($routeName, $arguments);
	}

	/**
	 * Creates an absolute url using a defined route
	 * @param string $routeName
	 * @param array $arguments args with param=>value, will be appended to the returned url
	 * @return string the url
	 *
	 * Returns an absolute url to the given route.
	 */
	#[\Override]
	public function linkToRouteAbsolute(string $routeName, array $arguments = []): string {
		return $this->getAbsoluteURL($this->linkToRoute($routeName, $arguments));
	}

	#[\Override]
	public function linkToOCSRouteAbsolute(string $routeName, array $arguments = []): string {
		// Returns `/subfolder/index.php/ocsapp/…` with `'htaccess.IgnoreFrontController' => false` in config.php
		// And `/subfolder/ocsapp/…` with `'htaccess.IgnoreFrontController' => true` in config.php
		$route = $this->router->generate('ocs.' . $routeName, $arguments, false);

		// Cut off `/subfolder`
		if (\OC::$WEBROOT !== '' && str_starts_with($route, \OC::$WEBROOT)) {
			$route = substr($route, \strlen(\OC::$WEBROOT));
		}

		if (str_starts_with($route, '/index.php/')) {
			$route = substr($route, 10);
		}

		// Remove `ocsapp/` bit
		$route = substr($route, 7);
		// Prefix with ocs/v2.php endpoint
		$route = '/ocs/v2.php' . $route;

		// Turn into an absolute URL
		return $this->getAbsoluteURL($route);
	}

	/**
	 * Creates an url
	 *
	 * @param string $appName app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *                    The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a url to the given app and file.
	 */
	#[\Override]
	public function linkTo(string $appName, string $file, array $args = []): string {
		$frontControllerActive = ($this->config->getSystemValueBool('htaccess.IgnoreFrontController', false) || getenv('front_controller_active') === 'true');

		if ($appName !== '') {
			$app_path = $this->getAppManager()->getAppPath($appName);
			// Check if the app is in the app folder
			if (file_exists($app_path . '/' . $file)) {
				if (str_ends_with($file, 'php')) {
					$urlLinkTo = \OC::$WEBROOT . '/index.php/apps/' . $appName;
					if ($frontControllerActive) {
						$urlLinkTo = \OC::$WEBROOT . '/apps/' . $appName;
					}
					$urlLinkTo .= ($file !== 'index.php') ? '/' . $file : '';
				} else {
					$urlLinkTo = $this->getAppManager()->getAppWebPath($appName) . '/' . $file;
				}
			} else {
				$urlLinkTo = \OC::$WEBROOT . '/' . $appName . '/' . $file;
			}
		} else {
			if (file_exists(\OC::$SERVERROOT . '/core/' . $file)) {
				$urlLinkTo = \OC::$WEBROOT . '/core/' . $file;
			} else {
				if ($frontControllerActive && $file === 'index.php') {
					$urlLinkTo = \OC::$WEBROOT . '/';
				} else {
					$urlLinkTo = \OC::$WEBROOT . '/' . $file;
				}
			}
		}

		if ($args && $query = http_build_query($args, '', '&')) {
			$urlLinkTo .= '?' . $query;
		}

		return $urlLinkTo;
	}

	/**
	 * Resolves the web path for an image asset.
	 *
	 * Lookup order prefers legacy filesystem theme assets first, then
	 * theming app overrides, then app and core fallback locations.
	 *
	 * At each lookup location, the requested filename is checked first as-is.
	 * If it is missing, a same-basename PNG may be returned, but only when a
	 * same-basename SVG is also missing at that location. If the requested
	 * file is missing and an SVG variant exists, lookup continues to the next
	 * location instead of falling back to PNG there.
	 *
	 * @param string $appName The app to resolve the image for. Empty string is treated as 'core'.
	 * @param string $file The requested image filename, including extension.
	 * @return string The resolved web path to the image asset.
	 * @throws \RuntimeException If no matching image can be found.
	 */
	#[\Override]
	public function imagePath(string $appName, string $file): string {
		if ($appName === '') {
			$appName = 'core';
		}

		$cache = $this->cacheFactory->createDistributed('imagePath-' . md5($this->getBaseUrl()) . '-');
		$cacheKey = $appName . '-' . $file;
		if ($key = $cache->get($cacheKey)) {
			return $key;
		}

		if ($appName === 'core') {
			$appPath = false;
		} else {
			try {
				$appPath = $this->getAppManager()->getAppPath($appName);
			} catch (AppPathNotFoundException $e) {
				throw new RuntimeException(
					'image asset not found for app ' . $appName . ' - requested image name: ' . $file . ' webroot: ' . \OC::$WEBROOT . ' serverroot: ' . \OC::$SERVERROOT
				);
			}
		}

		// image filename without extension; used to check for SVG before PNG.
		$basename = substr(basename($file), 0, -4); // FIXME: consider switching to pathinfo()

		$resolvedPath = null;

		//
		// Search for image assets in a deterministic order.
		//
		// Split into stages to make prioritization clearer.
		//
		// FIXME: The PNG fallback behavior matches the current implementation,
		// but the policy is odd and may deserve separate review.
		//

		// 1. Legacy filesystem themed assets (if active)
		$legacyThemeName = \OC_Util::getTheme();
		if ($legacyThemeName !== '') {
			$resolvedPath = $this->resolveLegacyThemeAppsImagePath($legacyThemeName, $appName, $file, $basename)
				?? $this->resolveLegacyThemeAppImagePath($legacyThemeName, $appName, $file, $basename)
				?? $this->resolveLegacyThemeCoreImagePath($legacyThemeName, $file, $basename);
		}

		// 2. Modern theming app overrides
		if ($resolvedPath === null) {
			$resolvedPath = $this->getThemingImageOverridePath($appName, $file);
		}

		// 3. app and core fallback locations
		if ($resolvedPath === null) {
			$resolvedPath = $this->resolveAppImagePath($appName, $appPath, $file, $basename)
				?? $this->resolveLegacyAppImagePath($appName, $file, $basename)
				?? $this->resolveCoreImagePath($file, $basename);
		}

		if ($resolvedPath !== null) {
			$cache->set($cacheKey, $resolvedPath);
			return $resolvedPath;
		}

		throw new RuntimeException(
			'image not found: image:' . $file . ' webroot:' . \OC::$WEBROOT . ' serverroot:' . \OC::$SERVERROOT
		);
	}

	/**
	 * Look for legacy themed assets: app specific image paths located in `/themes/$themeName/apps/$appName`
	 */
	private function resolveLegacyThemeAppsImagePath(string $legacyThemeName, string $appName, string $file, string $basename): ?string {
		$serverBasePath = \OC::$SERVERROOT . "/themes/$legacyThemeName/apps/$appName/img/";
		$webBasePath = \OC::$WEBROOT . "/themes/$legacyThemeName/apps/$appName/img/";

		return $this->resolveImagePathFromBases($serverBasePath, $webBasePath, $file, $basename);
	}

	/**
	 * Look for legacy themed assets: app specific image paths located in `/themes/$themeName/$appName`
	 */
	private function resolveLegacyThemeAppImagePath(string $legacyThemeName, string $appName, string $file, string $basename): ?string {
		if ($appName === '') {
			return null;
		}

		$serverBasePath = \OC::$SERVERROOT . "/themes/$legacyThemeName/$appName/img/";
		$webBasePath = \OC::$WEBROOT . "/themes/$legacyThemeName/$appName/img/";

		return $this->resolveImagePathFromBases($serverBasePath, $webBasePath, $file, $basename);
	}

	/**
	 * Look for legacy themed assets: core image paths located in `/themes/$themeName/core`
	 */
	private function resolveLegacyThemeCoreImagePath(string $legacyThemeName, string $file, string $basename): ?string {
		$serverBasePath = \OC::$SERVERROOT . "/themes/$legacyThemeName/core/img/";
		$webBasePath = \OC::$WEBROOT . "/themes/$legacyThemeName/core/img/";

		return $this->resolveImagePathFromBases($serverBasePath, $webBasePath, $file, $basename);
	}

	/**
	 * Look for app provided image assets
	 */
	private function resolveAppImagePath(string $appName, string|false $appPath, string $file, string $basename): ?string {
		if ($appPath === false) {
			return null;
		}

		$serverBasePath = $appPath . "/img/";
		$webBasePath = $this->getAppManager()->getAppWebPath($appName) . "/img/";

		return $this->resolveImagePathFromBases($serverBasePath, $webBasePath, $file, $basename);
	}

	/**
	 * Look up legacy app specific image assets located directly underneath $serverRoot
	 * FIXME: This may not be needed any longer.
	 */
	private function resolveLegacyAppImagePath(string $appName, string $file, string $basename): ?string {
		if ($appName === '') {
			return null;
		}

		$serverBasePath = \OC::$SERVERROOT . "/" . $appName . "/img/";
		$webBasePath = \OC::$WEBROOT . "/" . $appName . "/img/";

		return $this->resolveImagePathFromBases($serverBasePath, $webBasePath, $file, $basename);
	}

	/**
	 * Look up core image assets
	 */
	private function resolveCoreImagePath(string $file, string $basename): ?string {
		$serverBasePath = \OC::$SERVERROOT . "/core/img/";
		$webBasePath = \OC::$WEBROOT . "/core/img/";

		return $this->resolveImagePathFromBases($serverBasePath, $webBasePath, $file, $basename);
	}

	private function resolveImagePathFromBases(string $serverBasePath, string $webBasePath, string $file, string $basename): ?string {
		$filePath = $serverBasePath . $file;
		if (file_exists($filePath)) {
			return $webBasePath . $file;
		}

		$svgPath = $serverBasePath . $basename . '.svg';
		$pngPath = $serverBasePath . $basename . '.png';
		if (!file_exists($svgPath) && file_exists($pngPath)) {
			return $webBasePath . $basename . '.png';
		}

		return null;
	}
	
	private function getThemingImageOverridePath(string $appName, string $file): ?string {
		$installed = $this->config->getSystemValueBool('installed', false);
		if (!$installed) {
			return null;
		}

		$themingDefaults = Server::get('ThemingDefaults');

		if (!$themingDefaults instanceof ThemingDefaults) {
			return null;
		}

		$themingImagePath = $themingDefaults->replaceImagePath($appName, $file);
		return $themingImagePath ?: null;
	}

	/**
	 * Makes an URL absolute
	 * @param string $url the url in the Nextcloud host
	 * @return string the absolute version of the url
	 */
	#[\Override]
	public function getAbsoluteURL(string $url): string {
		$separator = str_starts_with($url, '/') ? '' : '/';

		if (\OC::$CLI && !\defined('PHPUNIT_RUN')) {
			return rtrim($this->config->getSystemValueString('overwrite.cli.url'), '/') . '/' . ltrim($url, '/');
		}
		// The Nextcloud web root could already be prepended.
		if (\OC::$WEBROOT !== '' && str_starts_with($url, \OC::$WEBROOT)) {
			$url = substr($url, \strlen(\OC::$WEBROOT));
		}

		return $this->getBaseUrl() . $separator . $url;
	}

	/**
	 * @param string $key
	 * @return string url to the online documentation
	 */
	#[\Override]
	public function linkToDocs(string $key): string {
		$theme = Server::get('ThemingDefaults');
		return $theme->buildDocLinkToKey($key);
	}

	/**
	 * Returns the URL of the default page based on the system configuration
	 * and the apps visible for the current user
	 * @return string
	 */
	#[\Override]
	public function linkToDefaultPageUrl(): string {
		// Deny the redirect if the URL contains a @
		// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
		if (isset($_REQUEST['redirect_url']) && !str_contains($_REQUEST['redirect_url'], '@')) {
			return $this->getAbsoluteURL(urldecode($_REQUEST['redirect_url']));
		}

		$defaultPage = $this->config->getAppValue('core', 'defaultpage');
		if ($defaultPage) {
			return $this->getAbsoluteURL($defaultPage);
		}

		$entryId = $this->getNavigationManager()->getDefaultEntryIdForUser();
		$entry = $this->getNavigationManager()->get($entryId);
		$href = (string)$entry['href'];
		if ($href === '') {
			throw new \InvalidArgumentException('Default navigation entry is missing href: ' . $entryId);
		}

		if (str_starts_with($href, $this->getBaseUrl())) {
			return $href;
		}

		if (str_starts_with($href, '/index.php/') && ($this->config->getSystemValueBool('htaccess.IgnoreFrontController', false) || getenv('front_controller_active') === 'true')) {
			$href = substr($href, 10);
		}

		return $this->getAbsoluteURL($href);
	}

	/**
	 * @return string base url of the current request
	 */
	#[\Override]
	public function getBaseUrl(): string {
		// BaseUrl can be equal to 'http(s)://' during the first steps of the initial setup.
		if ($this->baseUrl === null || $this->baseUrl === 'http://' || $this->baseUrl === 'https://') {
			$this->baseUrl = $this->request->getServerProtocol() . '://' . $this->request->getServerHost() . \OC::$WEBROOT;
		}
		return $this->baseUrl;
	}

	/**
	 * @return string webroot part of the base url
	 */
	#[\Override]
	public function getWebroot(): string {
		return \OC::$WEBROOT;
	}

	#[Override]
	public function linkToRemote(string $service): string {
		$remoteBase = $this->linkTo('', 'remote.php') . '/' . $service;
		return $this->getAbsoluteURL(
			$remoteBase . (($service[strlen($service) - 1] !== '/') ? '/' : '')
		);
	}
}
