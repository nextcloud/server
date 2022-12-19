<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Service;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\IConfig;

class JSDataService implements \JsonSerializable {
	private ThemingDefaults $themingDefaults;
	private Util $util;
	private IConfig $appConfig;
	private ThemesService $themesService;

	public function __construct(
		ThemingDefaults $themingDefaults,
		Util $util,
		IConfig $appConfig,
		ThemesService $themesService
	) {
		$this->themingDefaults = $themingDefaults;
		$this->util = $util;
		$this->appConfig = $appConfig;
		$this->themesService = $themesService;
	}

	public function jsonSerialize(): array {
		return [
			'name' => $this->themingDefaults->getName(),
			'url' => $this->themingDefaults->getBaseUrl(),
			'slogan' => $this->themingDefaults->getSlogan(),
			'color' => $this->themingDefaults->getColorPrimary(),
			'defaultColor' => $this->themingDefaults->getDefaultColorPrimary(),
			'imprintUrl' => $this->themingDefaults->getImprintUrl(),
			'privacyUrl' => $this->themingDefaults->getPrivacyUrl(),
			'inverted' => $this->util->invertTextColor($this->themingDefaults->getColorPrimary()),
			'cacheBuster' => $this->util->getCacheBuster(),
			'enabledThemes' => $this->themesService->getEnabledThemes(),
		];
	}
}
