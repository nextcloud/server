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

class DyslexiaFont extends DefaultTheme implements ITheme {

	public function getId(): string {
		return 'opendyslexic';
	}

	public function getType(): int {
		return ITheme::TYPE_FONT;
	}

	public function getTitle(): string {
		return $this->l->t('Dyslexia font');
	}

	public function getEnableLabel(): string {
		return $this->l->t('Enable dyslexia font');
	}

	public function getDescription(): string {
		return $this->l->t('OpenDyslexic is a free typeface/font designed to mitigate some of the common reading errors caused by dyslexia.');
	}

	public function getCSSVariables(): array {
		$variables = parent::getCSSVariables();
		$originalFontFace = $variables['--font-face'];

		$variables = [
			'--font-face' => 'OpenDyslexic, ' . $originalFontFace
		];

		return $variables;
	}

	public function getCustomCss(): string {
		$fontPathWoff = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Regular.woff');
		$fontPathOtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Regular.otf');
		$fontPathTtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Regular.ttf');
		$boldFontPathWoff = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Bold.woff');
		$boldFontPathOtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Bold.otf');
		$boldFontPathTtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Bold.ttf');

		return "
			@font-face {
				font-family: 'OpenDyslexic';
				font-style: normal;
				font-weight: 400;
				src: url('$fontPathWoff') format('woff'),
					 url('$fontPathOtf') format('opentype'),
					 url('$fontPathTtf') format('truetype');
			}
			
			@font-face {
				font-family: 'OpenDyslexic';
				font-style: normal;
				font-weight: 700;
				src: url('$boldFontPathWoff') format('woff'),
					 url('$boldFontPathOtf') format('opentype'),
					 url('$boldFontPathTtf') format('truetype');
			}
		";
	}
}
