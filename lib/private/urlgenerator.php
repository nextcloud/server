<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC;
use OC_Defaults;
use OCP\IURLGenerator;
use RuntimeException;

/**
 * Class to generate URLs
 */
class URLGenerator implements IURLGenerator {

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	/**
	 * Creates an url using a defined route
	 * @param string $route
	 * @param array $parameters
	 * @internal param array $args with param=>value, will be appended to the returned url
	 * @return string the url
	 *
	 * Returns a url to the given route.
	 */
	public function linkToRoute($route, $parameters = array()) {
		$urlLinkTo = \OC::$server->getRouter()->generate($route, $parameters);
		return $urlLinkTo;
	}

	/**
	 * Creates an absolute url using a defined route
	 * @param string $route
	 * @param array $parameters
	 * @internal param array $args with param=>value, will be appended to the returned url
	 * @return string the url
	 *
	 * Returns an absolute url to the given route.
	 */
	public function linkToRouteAbsolute($routeName, $arguments = array()) {
		return $this->getAbsoluteURL($this->linkToRoute($routeName, $arguments));
	}

	/**
	 * Creates an url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 *    The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a url to the given app and file.
	 */
	public function linkTo( $app, $file, $args = array() ) {
		$frontControllerActive=($this->config->getSystemValue('front_controller_active', 'false') == 'true');

		if( $app != '' ) {
			$app_path = \OC_App::getAppPath($app);
			// Check if the app is in the app folder
			if ($app_path && file_exists($app_path . '/' . $file)) {
				if (substr($file, -3) == 'php') {

					$urlLinkTo = \OC::$WEBROOT . '/index.php/apps/' . $app;
					if ($frontControllerActive) {
						$urlLinkTo = \OC::$WEBROOT . '/apps/' . $app;
					}
					$urlLinkTo .= ($file != 'index.php') ? '/' . $file : '';
				} else {
					$urlLinkTo = \OC_App::getAppWebPath($app) . '/' . $file;
				}
			} else {
				$urlLinkTo = \OC::$WEBROOT . '/' . $app . '/' . $file;
			}
		} else {
			if (file_exists(\OC::$SERVERROOT . '/core/' . $file)) {
				$urlLinkTo = \OC::$WEBROOT . '/core/' . $file;
			} else {
				if ($frontControllerActive && $file === 'index.php') {
					$urlLinkTo = \OC::$WEBROOT;
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
	 * @param string $app app
	 * @param string $image image name
	 * @throws \RuntimeException If the image does not exist
	 * @return string the url
	 *
	 * Returns the path to the image.
	 */
	public function imagePath($app, $image) {
		// Read the selected theme from the config file
		$theme = \OC_Util::getTheme();

		//if a theme has a png but not an svg always use the png
		$basename = substr(basename($image),0,-4);

		// Check if the app is in the app folder
		if (file_exists(\OC::$SERVERROOT . "/themes/$theme/apps/$app/img/$image")) {
			return \OC::$WEBROOT . "/themes/$theme/apps/$app/img/$image";
		} elseif (!file_exists(\OC::$SERVERROOT . "/themes/$theme/apps/$app/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/themes/$theme/apps/$app/img/$basename.png")) {
			return \OC::$WEBROOT . "/themes/$theme/apps/$app/img/$basename.png";
		} elseif (file_exists(\OC_App::getAppPath($app) . "/img/$image")) {
			return \OC_App::getAppWebPath($app) . "/img/$image";
		} elseif (!file_exists(\OC_App::getAppPath($app) . "/img/$basename.svg")
			&& file_exists(\OC_App::getAppPath($app) . "/img/$basename.png")) {
			return \OC_App::getAppPath($app) . "/img/$basename.png";
		} elseif (!empty($app) and file_exists(\OC::$SERVERROOT . "/themes/$theme/$app/img/$image")) {
			return \OC::$WEBROOT . "/themes/$theme/$app/img/$image";
		} elseif (!empty($app) and (!file_exists(\OC::$SERVERROOT . "/themes/$theme/$app/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/themes/$theme/$app/img/$basename.png"))) {
			return \OC::$WEBROOT . "/themes/$theme/$app/img/$basename.png";
		} elseif (!empty($app) and file_exists(\OC::$SERVERROOT . "/$app/img/$image")) {
			return \OC::$WEBROOT . "/$app/img/$image";
		} elseif (!empty($app) and (!file_exists(\OC::$SERVERROOT . "/$app/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/$app/img/$basename.png"))) {
			return \OC::$WEBROOT . "/$app/img/$basename.png";
		} elseif (file_exists(\OC::$SERVERROOT . "/themes/$theme/core/img/$image")) {
			return \OC::$WEBROOT . "/themes/$theme/core/img/$image";
		} elseif (!file_exists(\OC::$SERVERROOT . "/themes/$theme/core/img/$basename.svg")
			&& file_exists(\OC::$SERVERROOT . "/themes/$theme/core/img/$basename.png")) {
			return \OC::$WEBROOT . "/themes/$theme/core/img/$basename.png";
		} elseif (file_exists(\OC::$SERVERROOT . "/core/img/$image")) {
			return \OC::$WEBROOT . "/core/img/$image";
		} else {
			throw new RuntimeException('image not found: image:' . $image . ' webroot:' . \OC::$WEBROOT . ' serverroot:' . \OC::$SERVERROOT);
		}
	}


	/**
	 * Makes an URL absolute
	 * @param string $url the url in the ownCloud host
	 * @return string the absolute version of the url
	 */
	public function getAbsoluteURL($url) {
		$separator = $url[0] === '/' ? '' : '/';

		if (\OC::$CLI && !defined('PHPUNIT_RUN')) {
			return rtrim($this->config->getSystemValue('overwrite.cli.url'), '/') . '/' . ltrim($url, '/');
		}

		// The ownCloud web root can already be prepended.
		$webRoot = substr($url, 0, strlen(\OC::$WEBROOT)) === \OC::$WEBROOT
			? ''
			: \OC::$WEBROOT;

		return \OC_Request::serverProtocol() . '://' . \OC_Request::serverHost(). $webRoot . $separator . $url;
	}

	/**
	 * @param string $key
	 * @return string url to the online documentation
	 */
	public function linkToDocs($key) {
		$theme = new OC_Defaults();
		return $theme->buildDocLinkToKey($key);
	}
}
