<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

use OCP\Capabilities\ICapability;
use OCP\IURLGenerator;

/**
 * Class Capabilities
 *
 * @package OCA\Theming
 */
class Capabilities implements ICapability {

	/** @var ThemingDefaults */
	protected $theming;


	/** @var IURLGenerator */
	protected $url;

	/**
	 * @param ThemingDefaults $theming
	 * @param IURLGenerator $url
	 */
	public function __construct(ThemingDefaults $theming, IURLGenerator $url) {
		$this->theming = $theming;
		$this->url = $url;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array
	 */
	public function getCapabilities() {
		return [
			'theming' => [
				'name' => $this->theming->getName(),
				'url' => $this->theming->getBaseUrl(),
				'slogan' => $this->theming->getSlogan(),
				'color' => $this->theming->getMailHeaderColor(),
				'logo' => $this->url->getAbsoluteURL($this->theming->getLogo()),
				'background' => $this->url->getAbsoluteURL($this->theming->getBackground()),
			],
		];
	}
}
