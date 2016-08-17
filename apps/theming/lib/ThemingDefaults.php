<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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
	/** @var string */
	private $name;
	/** @var string */
	private $url;
	/** @var string */
	private $slogan;
	/** @var string */
	private $color;

	/**
	 * ThemingDefaults constructor.
	 *
	 * @param IConfig $config
	 * @param IL10N $l
	 * @param IURLGenerator $urlGenerator
	 * @param \OC_Defaults $defaults
	 */
	public function __construct(IConfig $config,
								IL10N $l,
								IURLGenerator $urlGenerator,
								\OC_Defaults $defaults
	) {
		parent::__construct();
		$this->config = $config;
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;

		$this->name = $defaults->getName();
		$this->url = $defaults->getBaseUrl();
		$this->slogan = $defaults->getSlogan();
		$this->color = $defaults->getMailHeaderColor();
	}

	public function getName() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}

	public function getHTMLName() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}

	public function getTitle() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}

	public function getEntity() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}

	public function getBaseUrl() {
		return $this->config->getAppValue('theming', 'url', $this->url);
	}

	public function getSlogan() {
		return $this->config->getAppValue('theming', 'slogan', $this->slogan);
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
	public function getMailHeaderColor() {
		return $this->config->getAppValue('theming', 'color', $this->color);
	}

	/**
	 * Increases the cache buster key
	 */
	private function increaseCacheBuster() {
		$cacheBusterKey = $this->config->getAppValue('theming', 'cachebuster', '0');
		$this->config->setAppValue('theming', 'cachebuster', (int)$cacheBusterKey+1);
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
				$returnValue = $this->getMailHeaderColor();
				break;
			default:
				$returnValue = '';
				break;
		}

		return $returnValue;
	}

}
