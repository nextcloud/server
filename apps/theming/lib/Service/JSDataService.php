<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Service;

use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;

class JSDataService implements \JsonSerializable {

	public function __construct(
		private ThemingDefaults $themingDefaults,
		private Util $util,
		private ThemesService $themesService,
	) {
		$this->themingDefaults = $themingDefaults;
		$this->util = $util;
		$this->themesService = $themesService;
	}

	public function jsonSerialize(): array {
		return [
			'name' => $this->themingDefaults->getName(),
			'slogan' => $this->themingDefaults->getSlogan(),

			'url' => $this->themingDefaults->getBaseUrl(),
			'imprintUrl' => $this->themingDefaults->getImprintUrl(),
			'privacyUrl' => $this->themingDefaults->getPrivacyUrl(),

			'primaryColor' => $this->themingDefaults->getColorPrimary(),
			'backgroundColor' => $this->themingDefaults->getColorBackground(),
			'defaultPrimaryColor' => $this->themingDefaults->getDefaultColorPrimary(),
			'defaultBackgroundColor' => $this->themingDefaults->getDefaultColorBackground(),
			'inverted' => $this->util->invertTextColor($this->themingDefaults->getColorPrimary()),

			'cacheBuster' => $this->util->getCacheBuster(),
			'enabledThemes' => $this->themesService->getEnabledThemes(),

			// deprecated use primaryColor
			'color' => $this->themingDefaults->getColorPrimary(),
			'' => 'color is deprecated since Nextcloud 29, use primaryColor instead'
		];
	}
}
