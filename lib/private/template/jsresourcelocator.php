<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\Template;

class JSResourceLocator extends ResourceLocator {
	/**
	 * @param string $script
	 */
	public function doFind($script) {
		$theme_dir = 'themes/'.$this->theme.'/';
		if (strpos($script, '3rdparty') === 0
			&& $this->appendIfExist($this->thirdpartyroot, $script.'.js')) {
			return;
		}

		if (strpos($script, '/l10n/') !== false) {
			// For language files we try to load them all, so themes can overwrite
			// single l10n strings without having to translate all of them.
			$found = 0;
			$found += $this->appendIfExist($this->serverroot, 'core/'.$script.'.js');
			$found += $this->appendIfExist($this->serverroot, $theme_dir.'core/'.$script.'.js');
			$found += $this->appendIfExist($this->serverroot, $script.'.js');
			$found += $this->appendIfExist($this->serverroot, $theme_dir.$script.'.js');
			$found += $this->appendIfExist($this->serverroot, $theme_dir.'apps/'.$script.'.js');

			if ($found) {
				return;
			}
		} else if ($this->appendIfExist($this->serverroot, $theme_dir.'apps/'.$script.'.js')
			|| $this->appendIfExist($this->serverroot, $theme_dir.$script.'.js')
			|| $this->appendIfExist($this->serverroot, $script.'.js')
			|| $this->appendIfExist($this->serverroot, $theme_dir.'core/'.$script.'.js')
			|| $this->appendIfExist($this->serverroot, 'core/'.$script.'.js')
		) {
			return;
		}

		$app = substr($script, 0, strpos($script, '/'));
		$script = substr($script, strpos($script, '/')+1);
		$app_path = \OC_App::getAppPath($app);
		$app_url = \OC_App::getAppWebPath($app);

		// missing translations files fill be ignored
		if (strpos($script, 'l10n/') === 0) {
			$this->appendIfExist($app_path, $script . '.js', $app_url);
			return;
		}
		$this->append($app_path, $script . '.js', $app_url);
	}

	/**
	 * @param string $script
	 */
	public function doFindTheme($script) {
	}
}
