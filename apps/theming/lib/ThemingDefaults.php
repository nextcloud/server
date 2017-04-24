<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\Theming;


use OCP\Files\IAppData;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class ThemingDefaults extends \OC_Defaults {

	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IAppData */
	private $appData;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var string */
	private $name;
	/** @var string */
	private $url;
	/** @var string */
	private $slogan;
	/** @var string */
	private $color;
	/** @var Util */
	private $util;

	/**
	 * ThemingDefaults constructor.
	 *
	 * @param IConfig $config
	 * @param IL10N $l
	 * @param IURLGenerator $urlGenerator
	 * @param \OC_Defaults $defaults
	 * @param IAppData $appData
	 * @param ICacheFactory $cacheFactory
	 * @param Util $util
	 */
	public function __construct(IConfig $config,
								IL10N $l,
								IURLGenerator $urlGenerator,
								\OC_Defaults $defaults,
								IAppData $appData,
								ICacheFactory $cacheFactory,
								Util $util
	) {
		$this->config = $config;
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
		$this->appData = $appData;
		$this->cacheFactory = $cacheFactory;
		$this->util = $util;

		$this->name = $defaults->getName();
		$this->url = $defaults->getBaseUrl();
		$this->slogan = $defaults->getSlogan();
		$this->color = $defaults->getColorPrimary();
	}

	public function getName() {
		return strip_tags($this->config->getAppValue('theming', 'name', $this->name));
	}

	public function getHTMLName() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}

	public function getTitle() {
		return $this->getName();
	}

	public function getEntity() {
		return $this->getName();
	}

	public function getBaseUrl() {
		return $this->config->getAppValue('theming', 'url', $this->url);
	}

	public function getSlogan() {
		return \OCP\Util::sanitizeHTML($this->config->getAppValue('theming', 'slogan', $this->slogan));
	}

	public function getShortFooter() {
		$slogan = $this->getSlogan();
		$footer = '<a href="'. $this->getBaseUrl() . '" target="_blank"' .
			' rel="noreferrer">' .$this->getEntity() . '</a>'.
			($slogan !== '' ? ' – ' . $slogan : '');

		return $footer;
	}

	/**
	 * Color that is used for the header as well as for mail headers
	 *
	 * @return string
	 */
	public function getColorPrimary() {
		return $this->config->getAppValue('theming', 'color', $this->color);
	}

	/**
	 * Themed logo url
	 *
	 * @return string
	 */
	public function getLogo() {
		$logo = $this->config->getAppValue('theming', 'logoMime', false);

		$logoExists = true;
		try {
			$this->appData->getFolder('images')->getFile('logo');
		} catch (\Exception $e) {
			$logoExists = false;
		}

		$cacheBusterCounter = $this->config->getAppValue('theming', 'cachebuster', '0');

		if(!$logo || !$logoExists) {
			return $this->urlGenerator->imagePath('core','logo.svg') . '?v=' . $cacheBusterCounter;
		}

		return $this->urlGenerator->linkToRoute('theming.Theming.getLogo') . '?v=' . $cacheBusterCounter;
	}

	/**
	 * Themed background image url
	 *
	 * @return string
	 */
	public function getBackground() {
		$backgroundLogo = $this->config->getAppValue('theming', 'backgroundMime',false);

		$backgroundExists = true;
		try {
			$this->appData->getFolder('images')->getFile('background');
		} catch (\Exception $e) {
			$backgroundExists = false;
		}

		if(!$backgroundLogo || !$backgroundExists) {
			return $this->urlGenerator->imagePath('core','background.jpg');
		}

		return $this->urlGenerator->linkToRoute('theming.Theming.getLoginBackground');
	}


	/**
	 * @return array scss variables to overwrite
	 */
	public function getScssVariables() {
		$cache = $this->cacheFactory->create('theming');
		if ($value = $cache->get('getScssVariables')) {
			return $value;
		}

		$variables = [
			'theming-cachebuster' => "'" . $this->config->getAppValue('theming', 'cachebuster', '0') . "'",
		];

		$variables['image-logo'] = "'".$this->urlGenerator->getAbsoluteURL($this->getLogo())."'";
		$variables['image-login-background'] = "'".$this->urlGenerator->getAbsoluteURL($this->getBackground())."'";

		if ($this->config->getAppValue('theming', 'color', null) !== null) {
			if ($this->util->invertTextColor($this->getColorPrimary())) {
				$colorPrimaryText = '#000000';
			} else {
				$colorPrimaryText = '#ffffff';
			}
			$variables['color-primary'] = $this->getColorPrimary();
			$variables['color-primary-text'] = $colorPrimaryText;
		}
		$cache->set('getScssVariables', $variables);
		return $variables;
	}

	/**
	 * Check if Imagemagick is enabled and if SVG is supported
	 * otherwise we can't render custom icons
	 *
	 * @return bool
	 */
	public function shouldReplaceIcons() {
		$cache = $this->cacheFactory->create('theming');
		if($value = $cache->get('shouldReplaceIcons')) {
			return (bool)$value;
		}
		$value = false;
		if(extension_loaded('imagick')) {
			$checkImagick = new \Imagick();
			if (count($checkImagick->queryFormats('SVG')) >= 1) {
				$value = true;
			}
			$checkImagick->clear();
		}
		$cache->set('shouldReplaceIcons', $value);
		return $value;
	}

	/**
	 * Increases the cache buster key
	 */
	private function increaseCacheBuster() {
		$cacheBusterKey = $this->config->getAppValue('theming', 'cachebuster', '0');
		$this->config->setAppValue('theming', 'cachebuster', (int)$cacheBusterKey+1);
		$this->cacheFactory->create('theming')->clear('getScssVariables');
	}

	/**
	 * Update setting in the database
	 *
	 * @param string $setting
	 * @param string $value
	 */
	public function set($setting, $value) {
		$this->config->setAppValue('theming', $setting, $value);
		$this->increaseCacheBuster();
	}

	/**
	 * Revert settings to the default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return string default value
	 */
	public function undo($setting) {
		$this->config->deleteAppValue('theming', $setting);
		$this->increaseCacheBuster();

		switch ($setting) {
			case 'name':
				$returnValue = $this->getEntity();
				break;
			case 'url':
				$returnValue = $this->getBaseUrl();
				break;
			case 'slogan':
				$returnValue = $this->getSlogan();
				break;
			case 'color':
				$returnValue = $this->getColorPrimary();
				break;
			default:
				$returnValue = '';
				break;
		}

		return $returnValue;
	}

}
