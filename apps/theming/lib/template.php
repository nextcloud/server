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

/**
 * Class Template
 *
 * Handle all the values which can be modified by this app
 *
 * @package OCA\Theming
 */
class Template {
	
	/** @var IConfig */
	private $config;
	
	/** @var  IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Init */
	private $init;

	/** @var string */
	private $name;

	/** @var string */
	private $url;

	/** @var string */
	private $slogan;

	/** @var string */
	private $color;

	/** @var string */
	private $logoName;

	/**
	 * Template constructor.
	 *
	 * @param IConfig $config
	 * @param IL10N $l
	 * @param IURLGenerator $urlGenerator
	 * @param Init $init
	 */
	public function __construct(IConfig $config,
								IL10N $l,
								IURLGenerator $urlGenerator,
								Init $init
	) {
		$this->config = $config;
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
		$this->init = $init;

		$this->name = 'Nextcloud';
		$this->url = 'https://nextcloud.com';
		$this->slogan = $this->l->t('a safe home for all your data');
		$this->color = '#0082c9';
		$this->logoName = 'logo-icon.svg';
	}

	public function getName() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}
	
	public function getUrl() {
		return $this->config->getAppValue('theming', 'url', $this->url);
	}

	public function getSlogan() {
		return $this->config->getAppValue('theming', 'slogan', $this->slogan);
	}

	public function getColor() {
		return $this->config->getAppValue('theming', 'color', $this->color);
	}

	public function getLogoName() {
		return $this->config->getAppValue('theming', 'logoName', $this->logoName);
	}

	/**
	 * update setting in the database
	 *
	 * @param $setting
	 * @param $value
	 */
	public function set($setting, $value) {
		$this->init->prepareThemeFolder();
		$this->config->setAppValue('theming', $setting, $value);
		$this->writeCSSFile();
	}

	/**
	 * revert settings to the default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return string default value
	 */
	public function undo($setting) {
		$returnValue = '';
		if ($this->$setting) {
			$this->config->setAppValue('theming', $setting, $this->$setting);
			$this->writeCSSFile();
			$returnValue = $this->$setting;
		}

		return $returnValue;
	}

	/**
	 * write setting to a css file
	 */
	private function writeCSSFile() {
		$logo = $this->getLogoName();
		$color = $this->getColor();

		$css = "
		#body-user #header,
        #body-settings #header,
        #body-public #header {
	        background-color: $color;
        }
        
        
        /* use logos from theme */
        #header .logo {
	        background-image: url('../img/$logo');
	        width: 250px;
	        height: 121px;
        }
        #header .logo-icon {
	        background-image: url('../img/$logo');
	        width: 62px;
	        height: 34px;
        }";

		$root = \OC::$SERVERROOT . '/themes/theming-app/core';

		file_put_contents($root . '/css/styles.css', $css);
	}

}
