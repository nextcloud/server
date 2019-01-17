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

use OCP\AppFramework\Utility\ITimeFactory;
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

	/** @var ITimeFactory */
	protected $timeFactory;

	/** @var string */
	private $iconVarRE = '/--(icon-[a-zA-Z0-9-]+):\s?url\(["\']?([a-zA-Z0-9-_\~\/\.\?\&\=\:\;\+\,]+)[^;]+;/m';

	/** @var string */
	private $fileName = 'icons-vars.css';

	/**
	 * @param ILogger $logger
	 * @param Factory $appDataFactory
	 * @param IURLGenerator $urlGenerator
	 * @param ITimeFactory $timeFactory
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function __construct(ILogger $logger,
								Factory $appDataFactory,
								IURLGenerator $urlGenerator,
								ITimeFactory $timeFactory) {
		$this->logger       = $logger;
		$this->appData      = $appDataFactory->get('css');
		$this->urlGenerator = $urlGenerator;
		$this->timeFactory  = $timeFactory;

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
			$data .= "--$icon: url('$url');";
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
		$mtime = $this->timeFactory->getTime();
		$file = $this->getCachedCSS();
		if ($file) {
			$mtime = $file->getMTime();
		}
		// Only inject once
		foreach (\OC_Util::$headers as $header) {
			if (
				array_key_exists('attributes', $header) &&
				array_key_exists('href', $header['attributes']) &&
				strpos($header['attributes']['href'], $this->fileName) !== false) {
				return;
			}
		}
		$linkToCSS = $this->urlGenerator->linkToRoute('core.Css.getCss', ['appName' => 'icons', 'fileName' => $this->fileName, 'v' => $mtime]);
		\OC_Util::addHeader('link', ['rel' => 'stylesheet', 'href' => $linkToCSS], null, true);
	}

}
