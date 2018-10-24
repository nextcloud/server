<?php
declare (strict_types = 1);
/**
 * @copyright Copyright (c) 2018, John Molakvoæ (skjnldsv@protonmail.com)
 *
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Template;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\ILogger;
use OCP\IURLGenerator;
use OC\Files\AppData\Factory;

class IconsCacher {

	/** @var ILogger */
	protected $logger;

	/** @var IAppData */
	protected $appData;

	/** @var ISimpleFolder */
	private $folder;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var string */
	private $iconVarRE = '/--(icon-[a-zA-Z0-9-]+):\s?url\(["\']?([a-zA-Z0-9-_\~\/\.\?\=\:\;\+\,]+)[^;]+;/m';

	/** @var string */
	private $fileName = 'icons-vars.css';

	/**
	 * @param ILogger $logger
	 * @param Factory $appDataFactory
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(ILogger $logger,
								Factory $appDataFactory,
								IURLGenerator $urlGenerator) {
		$this->logger       = $logger;
		$this->appData      = $appDataFactory->get('css');
		$this->urlGenerator = $urlGenerator;

		try {
			$this->folder = $this->appData->getFolder('icons');
		} catch (NotFoundException $e) {
			$this->folder = $this->appData->newFolder('icons');
		}
	}

	private function getIconsFromCss(string $css): array{
		preg_match_all($this->iconVarRE, $css, $matches, PREG_SET_ORDER);
		$icons = [];
		foreach ($matches as $icon) {
			$icons[$icon[1]] = $icon[2];
		}

		return $icons;
	}
	/**
	 * Parse and cache css
	 *
	 * @param string $css
	 */
	public function setIconsCss(string $css) {

		$cachedFile = $this->getCachedCSS();
		if (!$cachedFile) {
			$currentData = '';
		} else {
			$currentData = $cachedFile->getContent();
		}

		// remove :root
		$currentData = str_replace([':root {', '}'], '', $currentData);

		$icons = $this->getIconsFromCss($currentData . $css);

		$data = '';
		foreach ($icons as $icon => $url) {
			$base = $this->getRoutePrefix() . '/svg/';
			$svg = false;
			if (strpos($url, $base . 'core') === 0) {
				$cleanUrl = substr($url, strlen($base.'core'));
				$cleanUrl = substr($cleanUrl, 0, strpos($cleanUrl, '?'));
				$parts = explode('/', $cleanUrl);
				$color = array_pop($parts);
				$cleanUrl = implode('/', $parts);
				$location = \OC::$SERVERROOT . '/core/img/' . $cleanUrl . '.svg';
				$svg = file_get_contents($location);
			} elseif (strpos($url, $base) === 0) {
				$cleanUrl = substr($url, strlen($base));
				$cleanUrl = substr($cleanUrl, 0, strpos($cleanUrl, '?'));
				$parts = explode('/', $cleanUrl);
				$app = array_shift($parts);
				$color = array_pop($parts);
				$cleanUrl = implode('/', $parts);
				$location = \OC_App::getAppPath($app) . '/img/' . $cleanUrl . '.svg';
				if ($app === 'settings') {
					$location = \OC::$SERVERROOT . '/settings/img/' . $cleanUrl . '.svg';
				}
				$svg = file_get_contents($location);
			}
			if ($svg === false) {
				$this->logger->debug('Failed to get icon file ' . $location);
				$data .= "--$icon: url('$url');";
				continue;
			}
			// TODO: Copied from SvgController (we should put this into a separate method so the controller can use it as well)
			// add fill (fill is not present on black elements)
			$fillRe = '/<((circle|rect|path)((?!fill)[a-z0-9 =".\-#():;])+)\/>/mi';
			$svg = preg_replace($fillRe, '<$1 fill="#' . $color . '"/>', $svg);

			// replace any fill or stroke colors
			$svg = preg_replace('/stroke="#([a-z0-9]{3,6})"/mi', 'stroke="#' . $color . '"', $svg);
			$svg = preg_replace('/fill="#([a-z0-9]{3,6})"/mi', 'fill="#' . $color . '"', $svg);

			$encode = base64_encode($svg);
			$data .= "--$icon: url(data:image/svg+xml;base64,$encode) !default;";
		}

		if (strlen($data) > 0) {
			if (!$cachedFile) {
				$cachedFile = $this->folder->newFile($this->fileName);
			}

			$data = ":root {
				$data
			}";
			$cachedFile->putContent($data);
		}

		return preg_replace($this->iconVarRE, '', $css);
	}

	private function getRoutePrefix() {
		$frontControllerActive = (\OC::$server->getConfig()->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');
		$prefix = \OC::$WEBROOT . '/index.php';
		if ($frontControllerActive) {
			$prefix = \OC::$WEBROOT;
		}
		return $prefix;
	}

	/**
	 * Get icons css file
	 * @return ISimpleFile|boolean
	 */
	public function getCachedCSS() {
		try {
			return $this->folder->getFile($this->fileName);
		} catch (NotFoundException $e) {
			return false;
		}
	}

	public function injectCss() {
		// Only inject once
		foreach (\OC_Util::$headers as $header) {
			if (
				array_key_exists('attributes', $header) &&
				array_key_exists('href', $header['attributes']) &&
				strpos($header['attributes']['href'], $this->fileName) !== false) {
				return;
			}
		}
		$linkToCSS = $this->urlGenerator->linkToRoute('core.Css.getCss', ['appName' => 'icons', 'fileName' => $this->fileName]);
		\OC_Util::addHeader('link', ['rel' => 'stylesheet', 'href' => $linkToCSS], null, true);
	}

}