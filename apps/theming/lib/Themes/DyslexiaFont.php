<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$fontPathOtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Regular.otf');
		$boldFontPathOtf = $this->urlGenerator->linkTo('theming', 'fonts/OpenDyslexic-Bold.otf');

		return "
			@font-face {
				font-family: 'OpenDyslexic';
				font-style: normal;
				font-weight: 400;
				src: url('$fontPathOtf') format('opentype');
			}

			@font-face {
				font-family: 'OpenDyslexic';
				font-style: normal;
				font-weight: 700;
				src: url('$boldFontPathOtf') format('opentype');
			}
		";
	}
}
