<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Rudolf <github.com@daniel-rudolf.de>
 * @author Felix Epp <work@felixepp.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author mmccarn <mmccarn-github@mmsionline.us>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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

use OC\Route\Router;
use OCA\Theming\ThemingDefaults;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use RuntimeException;

/**
 * Class to generate URLs
 */
class URLGenerator implements IURLGenerator {
	/** @var IConfig */
	private $config;
	/** @var IUserSession */
	public $userSession;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var IRequest */
	private $request;
	/** @var Router */
	private $router;
	/** @var null|string */
	private $baseUrl = null;
	private ?IAppManager $appManager = null;

	public function __construct(IConfig $config,
		IUserSession $userSession,
		ICacheFactory $cacheFactory,
		IRequest $request,
		Router $router
	) {
		$this->config = $config;
		$this->userSession = $userSession;
		$this->cacheFactory = $cacheFactory;
		$this->request = $request;
		$this->router = $router;
	}

	private function getAppManager(): IAppManager {
		if ($this->appManager !== null) {
			return $this->appManager;
		}
		$this->appManager = \OCP\Server::get(IAppManager::class);
		return $this->appManager;
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
	public function linkToRouteAbsolute(string $routeName, array $arguments = []): string {
		return $this->getAbsoluteURL($this->linkToRoute($routeName, $arguments));
	}

	public function linkToOCSRouteAbsolute(string $routeName, array $arguments = []): string {
		// Returns `/subfolder/index.php/ocsapp/…` with `'htaccess.IgnoreFrontController' => false` in config.php
		// And `/subfolder/ocsapp/…` with `'htaccess.IgnoreFrontController' => true` in config.php
		$route = $this->router->generate('ocs.'.$routeName, $arguments, false);

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
	 *    The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a url to the given app and file.
	 */
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
	 * Creates path to an image
	 *
	 * @param string $appName app
	 * @param string $file image name
	 * @throws \RuntimeException If the image does not exist
	 * @return string the url
	 *
	 * Returns the path to the image.
	 */
	public function imagePath(string $appName, string $file): string {
		$cache = $this->cacheFactory->createDistributed('imagePath-'.md5($this->getBaseUrl()).'-');
		$cacheKey = $appName.'-'.$file;
		if ($key = $cache->get($cacheKey)) {
			return $key;
		}

		// Read the selected theme from the config file
		$theme = \OC_Util::getTheme();

		//if a theme has a png but not an svg always use the png
		$basename = substr(basename($file), 0, -4);

		try {
			$appPath = $this->getAppManager()->getAppPath($appName);
		} catch (AppPathNotFoundException $e) {
			if ($appName === 'core' || $appName === '') {
				$appName = 'core';
				$appPath = false;
			} else {
				throw new RuntimeException('image not found: image: ' . $file . ' webroot: ' . \OC::$WEBROOT . ' serverroot: ' . \OC::$SERVERROOT);
			}
		}

		// Check if the app is in the app folder
		$path = '';
		$themingEnabled = $this->config->getSystemValueBool('installed', false) && $this->getAppManager()->isEnabledForUser('theming');
		$themingImagePath = false;
		if ($themingEnabled) {
			$themingDefaults = \OC::$server->getThemingDefaults();
			if ($themingDefaults instanceof ThemingDefaults) {
				$themingImagePath = $themingDefaults->replaceImagePath($appName, $file);
			}
		}

		if (file_exists(\OC::$SERVERROOT . "/themes/$theme/apps/$appName/img/$file")) {
			$path = \OC::$WEBROOT . "/themes/$theme/apps/$appName/img/$file";
		} elseif (!file_exists(\OC::$SERVERROOT . "/themes/$theme/apps/$appName/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/themes/$theme/apps/$appName/img/$basename.png")) {
			$path = \OC::$WEBROOT . "/themes/$theme/apps/$appName/img/$basename.png";
		} elseif (!empty($appName) and file_exists(\OC::$SERVERROOT . "/themes/$theme/$appName/img/$file")) {
			$path = \OC::$WEBROOT . "/themes/$theme/$appName/img/$file";
		} elseif (!empty($appName) and (!file_exists(\OC::$SERVERROOT . "/themes/$theme/$appName/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/themes/$theme/$appName/img/$basename.png"))) {
			$path = \OC::$WEBROOT . "/themes/$theme/$appName/img/$basename.png";
		} elseif (file_exists(\OC::$SERVERROOT . "/themes/$theme/core/img/$file")) {
			$path = \OC::$WEBROOT . "/themes/$theme/core/img/$file";
		} elseif (!file_exists(\OC::$SERVERROOT . "/themes/$theme/core/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/themes/$theme/core/img/$basename.png")) {
			$path = \OC::$WEBROOT . "/themes/$theme/core/img/$basename.png";
		} elseif ($themingEnabled && $themingImagePath) {
			$path = $themingImagePath;
		} elseif ($appPath && file_exists($appPath . "/img/$file")) {
			$path = $this->getAppManager()->getAppWebPath($appName) . "/img/$file";
		} elseif ($appPath && !file_exists($appPath . "/img/$basename.svg")
			&& file_exists($appPath . "/img/$basename.png")) {
			$path = $this->getAppManager()->getAppWebPath($appName) . "/img/$basename.png";
		} elseif (!empty($appName) and file_exists(\OC::$SERVERROOT . "/$appName/img/$file")) {
			$path = \OC::$WEBROOT . "/$appName/img/$file";
		} elseif (!empty($appName) and (!file_exists(\OC::$SERVERROOT . "/$appName/img/$basename.svg")
				&& file_exists(\OC::$SERVERROOT . "/$appName/img/$basename.png"))) {
			$path = \OC::$WEBROOT . "/$appName/img/$basename.png";
		} elseif (file_exists(\OC::$SERVERROOT . "/core/img/$file")) {
			$path = \OC::$WEBROOT . "/core/img/$file";
		} elseif (!file_exists(\OC::$SERVERROOT . "/core/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/core/img/$basename.png")) {
			$path = \OC::$WEBROOT . "/themes/$theme/core/img/$basename.png";
		}

		if ($path !== '') {
			$cache->set($cacheKey, $path);
			return $path;
		}

		throw new RuntimeException('image not found: image:' . $file . ' webroot:' . \OC::$WEBROOT . ' serverroot:' . \OC::$SERVERROOT);
	}


	/**
	 * Makes an URL absolute
	 * @param string $url the url in the ownCloud host
	 * @return string the absolute version of the url
	 */
	public function getAbsoluteURL(string $url): string {
		$separator = str_starts_with($url, '/') ? '' : '/';

		if (\OC::$CLI && !\defined('PHPUNIT_RUN')) {
			return rtrim($this->config->getSystemValueString('overwrite.cli.url'), '/') . '/' . ltrim($url, '/');
		}
		// The ownCloud web root can already be prepended.
		if (\OC::$WEBROOT !== '' && str_starts_with($url, \OC::$WEBROOT)) {
			$url = substr($url, \strlen(\OC::$WEBROOT));
		}

		return $this->getBaseUrl() . $separator . $url;
	}

	/**
	 * @param string $key
	 * @return string url to the online documentation
	 */
	public function linkToDocs(string $key): string {
		$theme = \OC::$server->getThemingDefaults();
		return $theme->buildDocLinkToKey($key);
	}

	/**
	 * Returns the URL of the default page based on the system configuration
	 * and the apps visible for the current user
	 * @return string
	 */
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

		$appId = $this->getAppManager()->getDefaultAppForUser();

		if ($this->config->getSystemValueBool('htaccess.IgnoreFrontController', false)
			|| getenv('front_controller_active') === 'true') {
			return $this->getAbsoluteURL('/apps/' . $appId . '/');
		}

		return $this->getAbsoluteURL('/index.php/apps/' . $appId . '/');
	}

	/**
	 * @return string base url of the current request
	 */
	public function getBaseUrl(): string {
		// BaseUrl can be equal to 'http(s)://' during the first steps of the initial setup.
		if ($this->baseUrl === null || $this->baseUrl === "http://" || $this->baseUrl === "https://") {
			$this->baseUrl = $this->request->getServerProtocol() . '://' . $this->request->getServerHost() . \OC::$WEBROOT;
		}
		return $this->baseUrl;
	}

	/**
	 * @return string webroot part of the base url
	 */
	public function getWebroot(): string {
		return \OC::$WEBROOT;
	}
}
