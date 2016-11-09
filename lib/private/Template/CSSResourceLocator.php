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

class CSSResourceLocator extends ResourceLocator {

	protected $appData;

	/**
	 * @param \OCP\ILogger $logger
	 * @param string $theme
	 * @param array $core_map
	 * @param array $party_map
	 * @param IAppData $appData
	 */
	public function __construct(\OCP\ILogger $logger, $theme, $core_map, $party_map, $appData) {
		$this->appData = $appData;
		parent::__construct($logger, $theme, $core_map, $party_map);
	}

	/**
	 * @param string $style
	 */
	public function doFind($style) {
		if (strpos($style, '3rdparty') === 0
			&& $this->appendIfExist($this->thirdpartyroot, $style.'.css')
			|| $this->cacheAndAppendScssIfExist($this->serverroot, $style.'.scss', $this->appData)
			|| $this->cacheAndAppendScssIfExist($this->serverroot, 'core/'.$style.'.scss', $this->appData)
			|| $this->appendIfExist($this->serverroot, $style.'.css')
			|| $this->appendIfExist($this->serverroot, 'core/'.$style.'.css')
		) {
			return;
		}
		$app = substr($style, 0, strpos($style, '/'));
		$style = substr($style, strpos($style, '/')+1);
		$app_path = \OC_App::getAppPath($app);
		$app_url = \OC_App::getAppWebPath($app);
		$this->append($app_path, $style.'.css', $app_url);
	}

	/**
	 * @param string $style
	 */
	public function doFindTheme($style) {
		$theme_dir = 'themes/'.$this->theme.'/';
		$this->appendIfExist($this->serverroot, $theme_dir.'apps/'.$style.'.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir.$style.'.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir.'core/'.$style.'.css');
	}
}
