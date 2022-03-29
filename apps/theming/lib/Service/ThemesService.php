<?php
/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\ITheme;

class ThemesService {

	/** @var ITheme[] */
	private array $themesProviders;

	public function __construct(DefaultTheme $defaultTheme,
								DarkTheme $darkTheme,
								DarkHighContrastTheme $darkHighContrastTheme,
								HighContrastTheme $highContrastTheme) {
		// Register themes
		$this->themesProviders = [
			$defaultTheme->getId()			=> $defaultTheme,
			$darkTheme->getId()				=> $darkTheme,
			$darkHighContrastTheme->getId()	=> $darkHighContrastTheme,
			$highContrastTheme->getId()		=> $highContrastTheme,
		];
	}

	public function getThemes() {
		return $this->themesProviders;
	}

	public function getThemeVariables(string $id) {
		return $this->themesProviders[$id]->getCSSVariables();
	}
}
