<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
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
use OCP\Files\IRootFolder;

class Util {

	private $config;
	private $rootFolder;

	public function __construct(IConfig $config, IRootFolder $rootFolder) {
		$this->config = $config;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param string $color rgb color value
	 * @return bool
	 */
	public function invertTextColor($color) {
		$l = $this->calculateLuminance($color);
		if($l>0.5) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * get color for on-page elements:
	 * theme color by default, grey if theme color is to bright
	 * @param $color
	 * @return string
	 */
	public function elementColor($color) {
		$l = $this->calculateLuminance($color);
		if($l>0.8) {
			return '#555555';
		} else {
			return $color;
		}
	}

	/**
	 * @param string $color rgb color value
	 * @return float
	 */
	public function calculateLuminance($color) {
		$hex = preg_replace("/[^0-9A-Fa-f]/", '', $color);
		if (strlen($hex) === 3) {
			$hex = $hex{0} . $hex{0} . $hex{1} . $hex{1} . $hex{2} . $hex{2};
		}
		if (strlen($hex) !== 6) {
			return 0;
		}
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
		return (0.299 * $r + 0.587 * $g + 0.114 * $b)/255;
	}

	/**
	 * @param $color
	 * @return string base64 encoded radio button svg
	 */
	public function generateRadioButton($color) {
		$radioButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16">' .
			'<path d="M8 1a7 7 0 0 0-7 7 7 7 0 0 0 7 7 7 7 0 0 0 7-7 7 7 0 0 0-7-7zm0 1a6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 1-6-6 6 6 0 0 1 6-6zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="'.$color.'"/></svg>';
		return base64_encode($radioButtonIcon);
	}


	/**
	 * @param $app app name
	 * @return string path to app icon / logo
	 */
	public function getAppIcon($app) {
		$appPath = \OC_App::getAppPath($app);

		$icon = $appPath . '/img/' . $app . '.svg';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/app.svg';
		if(file_exists($icon)) {
			return $icon;
		}

		if($this->config->getAppValue('theming', 'logoMime', '') !== '' && $this->rootFolder->nodeExists('/themedinstancelogo')) {
			return $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/') . '/themedinstancelogo';
		}
		return \OC::$SERVERROOT . '/core/img/logo.svg';
	}

	/**
	 * @param $app app name
	 * @param $image relative path to image in app folder
	 * @return string absolute path to image
	 */
	public function getAppImage($app, $image) {
		$appPath = \OC_App::getAppPath($app);

		if($app==="core") {
			$icon = \OC::$SERVERROOT . '/core/img/' . $image;
			if(file_exists($icon)) {
				return $icon;
			}
		}

		$icon = $appPath . '/img/' . $image;
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.svg';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.png';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.gif';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.jpg';
		if(file_exists($icon)) {
			return $icon;
		}
		return false;
	}

	/**
	 * replace default color with a custom one
	 *
	 * @param $svg content of a svg file
	 * @param $color color to match
	 * @return string
	 */
	public function colorizeSvg($svg, $color) {
		$svg = preg_replace('/#0082c9/i', $color, $svg);
		return $svg;
	}

}
