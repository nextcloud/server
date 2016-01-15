<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\L10N;

use OCP\L10N\IFactory;

/**
 * A factory that generates language instances
 */
class Factory implements IFactory {
	/**
	 * cached instances
	 * @var array Structure: Lang => App => \OCP\IL10N
	 */
	protected $instances = [];

	/**
	 * @var array Structure: App => string[]
	 */
	protected $availableLanguages = [];

	/**
	 * Get a language instance
	 *
	 * @param string $app
	 * @param string|null $lang
	 * @return \OCP\IL10N
	 */
	public function get($app, $lang = null) {
		$key = $lang;
		if ($key === null) {
			$key = 'null';
		}

		if (!isset($this->instances[$key][$app])) {
			$this->instances[$key][$app] = new \OC_L10N($app, $lang);
		}

		return $this->instances[$key][$app];
	}

	/**
	 * Find all available languages for an app
	 *
	 * @param string|null $app App id or null for core
	 * @return array an array of available languages
	 */
	public function findAvailableLanguages($app = null) {
		$key = $app;
		if ($key === null) {
			$key = 'null';
		}

		// also works with null as key
		if (!empty($this->availableLanguages[$key])) {
			return $this->availableLanguages[$key];
		}

		$available = ['en']; //english is always available
		$dir = $this->findL10nDir($app);
		if (is_dir($dir)) {
			$files = scandir($dir);
			if ($files !== false) {
				foreach ($files as $file) {
					if (substr($file, -5) === '.json' && substr($file, 0, 4) !== 'l10n') {
						$available[] = substr($file, 0, -5);
					}
				}
			}
		}

		$this->availableLanguages[$key] = $available;
		return $available;
	}

	/**
	 * @param string|null $app App id or null for core
	 * @param string $lang
	 * @return bool
	 */
	public function languageExists($app, $lang) {
		if ($lang === 'en') {//english is always available
			return true;
		}

		$languages = $this->findAvailableLanguages($app);
		return array_search($lang, $languages);
	}

	/**
	 * find the l10n directory
	 *
	 * @param string $app App id or empty string for core
	 * @return string directory
	 */
	protected function findL10nDir($app = '') {
		if ($app !== '') {
			// Check if the app is in the app folder
			if (file_exists(\OC_App::getAppPath($app) . '/l10n/')) {
				return \OC_App::getAppPath($app) . '/l10n/';
			} else {
				return \OC::$SERVERROOT . '/' . $app . '/l10n/';
			}
		}
		return \OC::$SERVERROOT.'/core/l10n/';
	}
}
