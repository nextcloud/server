<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Settings;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Controller\ThemingController;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class Admin implements IDelegatedSettings {

	public function __construct(
		private string $appName,
		private IConfig $config,
		private IL10N $l,
		private ThemingDefaults $themingDefaults,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
		private ImageManager $imageManager,
		private INavigationManager $navigationManager,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$themable = true;
		$errorMessage = '';
		$theme = $this->config->getSystemValue('theme', '');
		if ($theme !== '') {
			$themable = false;
			$errorMessage = $this->l->t('You are already using a custom theme. Theming app settings might be overwritten by that.');
		}

		$allowedMimeTypes = array_reduce(ThemingController::VALID_UPLOAD_KEYS, function ($carry, $key) {
			$carry[$key] = $this->imageManager->getSupportedUploadImageFormats($key);
			return $carry;
		}, []);

		$this->initialState->provideInitialState('adminThemingParameters', [
			'isThemable' => $themable,
			'notThemableErrorMessage' => $errorMessage,
			'name' => $this->themingDefaults->getEntity(),
			'url' => $this->themingDefaults->getBaseUrl(),
			'slogan' => $this->themingDefaults->getSlogan(),
			'primaryColor' => $this->themingDefaults->getDefaultColorPrimary(),
			'backgroundColor' => $this->themingDefaults->getDefaultColorBackground(),
			'logoMime' => $this->config->getAppValue(Application::APP_ID, 'logoMime', ''),
			'allowedMimeTypes' => $allowedMimeTypes,
			'backgroundURL' => $this->imageManager->getImageUrl('background'),
			'defaultBackgroundURL' => $this->urlGenerator->linkTo(Application::APP_ID, 'img/background/' . BackgroundService::DEFAULT_BACKGROUND_IMAGE),
			'defaultBackgroundColor' => BackgroundService::DEFAULT_BACKGROUND_COLOR,
			'backgroundMime' => $this->config->getAppValue(Application::APP_ID, 'backgroundMime', ''),
			'logoheaderMime' => $this->config->getAppValue(Application::APP_ID, 'logoheaderMime', ''),
			'faviconMime' => $this->config->getAppValue(Application::APP_ID, 'faviconMime', ''),
			'legalNoticeUrl' => $this->themingDefaults->getImprintUrl(),
			'privacyPolicyUrl' => $this->themingDefaults->getPrivacyUrl(),
			'docUrl' => $this->urlGenerator->linkToDocs('admin-theming'),
			'docUrlIcons' => $this->urlGenerator->linkToDocs('admin-theming-icons'),
			'canThemeIcons' => $this->imageManager->shouldReplaceIcons(),
			'userThemingDisabled' => $this->themingDefaults->isUserThemingDisabled(),
			'defaultApps' => $this->navigationManager->getDefaultEntryIds(),
		]);

		Util::addScript($this->appName, 'admin-theming');

		return new TemplateResponse($this->appName, 'settings-admin');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
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
	 */
	public function getPriority(): int {
		return 5;
	}

	public function getName(): ?string {
		return null; // Only one setting in this section
	}

	public function getAuthorizedAppConfig(): array {
		return [
			$this->appName => '/.*/',
		];
	}
}
