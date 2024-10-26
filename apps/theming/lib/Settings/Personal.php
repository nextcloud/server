<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Settings;

use OCA\Theming\ITheme;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\INavigationManager;
use OCP\Settings\ISettings;
use OCP\Util;

class Personal implements ISettings {

	public function __construct(
		protected string $appName,
		private string $userId,
		private IConfig $config,
		private ThemesService $themesService,
		private IInitialState $initialStateService,
		private ThemingDefaults $themingDefaults,
		private INavigationManager $navigationManager,
	) {
	}

	public function getForm(): TemplateResponse {
		$enforcedTheme = $this->config->getSystemValueString('enforce_theme', '');

		$themes = array_map(function ($theme) {
			return [
				'id' => $theme->getId(),
				'type' => $theme->getType(),
				'title' => $theme->getTitle(),
				'enableLabel' => $theme->getEnableLabel(),
				'description' => $theme->getDescription(),
				'enabled' => $this->themesService->isEnabled($theme),
			];
		}, $this->themesService->getThemes());

		if ($enforcedTheme !== '') {
			$themes = array_filter($themes, function ($theme) use ($enforcedTheme) {
				return $theme['type'] !== ITheme::TYPE_THEME || $theme['id'] === $enforcedTheme;
			});
		}

		// Get the default entry enforced by admin
		$forcedDefaultEntry = $this->navigationManager->getDefaultEntryIdForUser(null, false);

		/** List of all shipped backgrounds */
		$this->initialStateService->provideInitialState('shippedBackgrounds', BackgroundService::SHIPPED_BACKGROUNDS);

		/**
		 * Admin theming
		 */
		$this->initialStateService->provideInitialState('themingDefaults', [
			/** URL of admin configured background image */
			'backgroundImage' => $this->themingDefaults->getBackground(),
			/** `backgroundColor` if disabled, mime type if defined and empty by default */
			'backgroundMime' => $this->config->getAppValue('theming', 'backgroundMime', ''),
			/** Admin configured background color */
			'backgroundColor' => $this->themingDefaults->getDefaultColorBackground(),
			/** Admin configured primary color */
			'primaryColor' => $this->themingDefaults->getDefaultColorPrimary(),
			/** Nextcloud default background image */
			'defaultShippedBackground' => BackgroundService::DEFAULT_BACKGROUND_IMAGE,
		]);

		$this->initialStateService->provideInitialState('userBackgroundImage', $this->config->getUserValue($this->userId, 'theming', 'background_image', BackgroundService::BACKGROUND_DEFAULT));
		$this->initialStateService->provideInitialState('themes', array_values($themes));
		$this->initialStateService->provideInitialState('enforceTheme', $enforcedTheme);
		$this->initialStateService->provideInitialState('isUserThemingDisabled', $this->themingDefaults->isUserThemingDisabled());
		$this->initialStateService->provideInitialState('enableBlurFilter', $this->config->getUserValue($this->userId, 'theming', 'force_enable_blur_filter', ''));
		$this->initialStateService->provideInitialState('navigationBar', [
			'userAppOrder' => json_decode($this->config->getUserValue($this->userId, 'core', 'apporder', '[]'), true, flags:JSON_THROW_ON_ERROR),
			'enforcedDefaultApp' => $forcedDefaultEntry
		]);

		Util::addScript($this->appName, 'personal-theming');

		return new TemplateResponse($this->appName, 'settings-personal');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
	 */
	public function getSection(): string {
		return $this->appName;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority(): int {
		return 40;
	}
}
