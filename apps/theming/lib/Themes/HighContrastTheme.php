<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\Theming\Themes;

use OCA\Theming\ITheme;

class HighContrastTheme extends DefaultTheme implements ITheme {

	public function getId(): string {
		return 'highcontrast';
	}

	public function getMediaQuery(): string {
		return '(prefers-contrast: more)';
	}

	public function getTitle(): string {
		return $this->l->t('High contrast mode');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable high contrast mode');
	}

	public function getDescription(): string {
		return $this->l->t('A high contrast mode to ease your navigation. Visual quality will be reduced but clarity will be increased.');
	}

	public function getCSSVariables(): array {
		$variables = parent::getCSSVariables();
		$colorMainText = '#000000';
		$colorMainBackground = '#ffffff';

		$variables['--color-main-background'] = $colorMainBackground;
		$variables['--color-main-text'] = $colorMainText;

		$variables['--color-background-dark'] = $this->util->darken($colorMainBackground, 30);
		$variables['--color-background-darker'] = $this->util->darken($colorMainBackground, 30);

		$variables['--color-placeholder-light'] = $this->util->darken($colorMainBackground, 30);
		$variables['--color-placeholder-dark'] = $this->util->darken($colorMainBackground, 45);

		$variables['--color-text-maxcontrast'] = 'var(--color-main-text)';
		$variables['--color-text-light'] = 'var(--color-main-text)';
		$variables['--color-text-lighter'] = 'var(--color-main-text)';

		// used for the icon loading animation
		$variables['--color-loading-light'] = '#dddddd';
		$variables['--color-loading-dark'] = '#000000';

		$variables['--color-box-shadow'] = 'var(--color-main-text)';

		$variables['--color-border'] = $this->util->darken($colorMainBackground, 50);
		$variables['--color-border-dark'] = $this->util->darken($colorMainBackground, 50);

		return $variables;
	}

	public function getCustomCss(string $prefix): string {
		return "
			$prefix [class^='icon-'], $prefix [class*=' icon-'],
			$prefix .action,
			$prefix #appmenu li a,
			$prefix .menutoggle {
				opacity: 1 !important;
			}
		";
	}
}
