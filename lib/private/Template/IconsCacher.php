<?php
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
	private $iconVarRE = '/--([a-z0-9-]+): url\(["\']([a-z0-9-\/]+)[^;]+;/m';

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

		try {
			$currentData = $this->folder->getFile($this->fileName)->getContent();
		} catch (NotFoundException $e) {
			$currentData = '';
		}

		// remove :root
		$currentData = str_replace([':root {', '}'], '', $currentData);

		$icons = $this->getIconsFromCss($currentData . $css);

		$data = '';
		foreach ($icons as $icon => $url) {
			$data .= "--$icon: url('$url?v=1');";
		}

		if (strlen($data) > 0) {
			try {
				$cachedfile = $this->folder->getFile($this->fileName);
			} catch (NotFoundException $e) {
				$cachedfile = $this->folder->newFile($this->fileName);
			}

			$data = ":root {
				$data
			}";
			$cachedfile->putContent($data);
		}

		return preg_replace($this->iconVarRE, '', $css);
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
		$linkToCSS = substr($this->urlGenerator->linkToRoute('core.Css.getCss', ['appName' => 'icons', 'fileName' => $this->fileName]), strlen(\OC::$WEBROOT));
		\OCP\Util::addHeader('link', ['rel' => 'stylesheet', 'href' => $linkToCSS]);
	}

}