<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Themes;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;

trait CommonThemeTrait {
	public Util $util;
	public ThemingDefaults $themingDefaults;

	protected bool $isDarkVariant = false;

	/**
	 * Generate primary-related variables
	 * This is shared between multiple themes because colorMainBackground and colorMainText
	 * will change in between.
	 */
	protected function generatePrimaryVariables(string $colorMainBackground, string $colorMainText, bool $highContrast = false): array {
		$isBrightColor = $this->util->isBrightColor($colorMainBackground);
		$colorPrimaryElement = $this->util->elementColor($this->primaryColor, $isBrightColor, $colorMainBackground, $highContrast);
		$colorPrimaryElementText = $this->util->getTextColor($colorPrimaryElement);
		$colorPrimaryLight = $this->util->mix($colorPrimaryElement, $colorMainBackground, -80);
		$colorPrimaryLightText = $this->util->getTextColor($colorPrimaryLight);
		$invertPrimaryTextColor = $this->util->invertTextColor($colorPrimaryElement);

		// primary related colors
		return [
			// invert filter if primary is too bright
			// to be used for legacy reasons only. Use inline
			// svg with proper css variable instead or material
			// design icons.
			// ⚠️ Using 'no' as a value to make sure we specify an
			// invalid one with no fallback. 'unset' could here fallback to some
			// other theme with media queries
			'--primary-invert-if-bright' => $this->util->invertTextColor($colorPrimaryElement) ? 'invert(100%)' : 'no',
			'--primary-invert-if-dark' => $this->util->invertTextColor($colorPrimaryElement) ? 'no' : 'invert(100%)',

			'--color-primary' => $this->primaryColor,
			'--color-primary-text' => $this->util->getTextColor($this->primaryColor),
			'--color-primary-hover' => $this->util->mix($this->primaryColor, $colorMainBackground, 60),
			// used for buttons, inputs...
			'--color-primary-element' => $colorPrimaryElement,
			'--color-primary-element-hover' => $invertPrimaryTextColor ? $this->util->lighten($colorPrimaryElement, 7) : $this->util->darken($colorPrimaryElement, 7),
			'--color-primary-element-text' => $colorPrimaryElementText,
			// mostly used for disabled states
			'--color-primary-element-text-dark' => $invertPrimaryTextColor ? $this->util->lighten($colorPrimaryElementText, 7) : $this->util->darken($colorPrimaryElementText, 7),

			// used for hover/focus states
			'--color-primary-light' => $colorPrimaryLight,
			'--color-primary-light-hover' => $this->util->mix($colorPrimaryLight, $colorPrimaryLightText, 90),
			'--color-primary-light-text' => $this->util->mix($this->primaryColor, $colorPrimaryLightText, -20),
			// deprecated aliases
			'--color-primary-element-light' => 'var(--color-primary-light)',
			'--color-primary-element-light-text' => 'var(--color-primary-light-text)',
			'--color-primary-element-light-hover' => 'var(--color-primary-light-hover)',

			// to use like this: background-image: var(--gradient-primary-background);
			'--gradient-primary-background' => 'linear-gradient(40deg, var(--color-primary) 0%, var(--color-primary-hover) 100%)',
		];
	}

	/**
	 * Generate admin theming background-related variables
	 */
	protected function generateGlobalBackgroundVariables(): array {
		$backgroundDeleted = $this->config->getAppValue(Application::APP_ID, 'backgroundMime', '') === 'backgroundColor';
		$hasCustomLogoHeader = $this->util->isLogoThemed();
		$backgroundColor = $this->themingDefaults->getColorBackground();

		// Default last fallback values
		$variables = [
			'--color-background-plain' => $backgroundColor,
			'--color-background-plain-text' => $this->util->invertTextColor($backgroundColor) ? '#000000' : '#ffffff',
			'--background-image-invert-if-bright' => $this->util->invertTextColor($backgroundColor) ? 'invert(100%)' : 'no',
		];

		// Register image variables only if custom-defined
		foreach (ImageManager::SUPPORTED_IMAGE_KEYS as $image) {
			if ($this->imageManager->hasImage($image)) {
				$imageUrl = $this->imageManager->getImageUrl($image);
				$variables["--image-$image"] = "url('" . $imageUrl . "')";
			} elseif ($image === 'background') {
				// Apply default background if nothing is configured
				$variables['--image-background'] = "url('" . $this->themingDefaults->getBackground($this->isDarkVariant) . "')";
			}
		}

		// If a background has been requested let's not define the background image
		if ($backgroundDeleted) {
			$variables['--image-background'] = 'none';
		}

		if ($hasCustomLogoHeader) {
			// prevent inverting the logo on bright colors if customized
			$variables['--image-logoheader-custom'] = 'true';
		}

		return $variables;
	}

	/**
	 * Generate user theming background-related variables
	 */
	protected function generateUserBackgroundVariables(): array {
		$user = $this->userSession->getUser();
		if ($user !== null
			&& !$this->themingDefaults->isUserThemingDisabled()
			&& $this->appManager->isEnabledForUser(Application::APP_ID)) {
			$backgroundImage = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT);
			$backgroundColor = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_color', $this->themingDefaults->getColorBackground());

			$currentVersion = (int)$this->config->getUserValue($user->getUID(), Application::APP_ID, 'userCacheBuster', '0');
			$isBackgroundBright = $this->util->invertTextColor($backgroundColor);
			$backgroundTextColor = $this->util->invertTextColor($backgroundColor) ? '#000000' : '#ffffff';

			$variables = [
				'--color-background-plain' => $backgroundColor,
				'--color-background-plain-text' => $backgroundTextColor,
				'--background-image-invert-if-bright' => $isBackgroundBright ? 'invert(100%)' : 'no',
			];

			// Only use a background color without an image
			if ($backgroundImage === BackgroundService::BACKGROUND_COLOR) {
				// Might be defined already by admin theming, needs to be overridden
				$variables['--image-background'] = 'none';
			}

			// The user uploaded a custom background
			if ($backgroundImage === BackgroundService::BACKGROUND_CUSTOM) {
				$cacheBuster = substr(sha1($user->getUID() . '_' . $currentVersion), 0, 8);
				$variables['--image-background'] = "url('" . $this->urlGenerator->linkToRouteAbsolute('theming.userTheme.getBackground') . "?v=$cacheBuster')";
			}

			// The user picked a shipped background
			if (isset(BackgroundService::SHIPPED_BACKGROUNDS[$backgroundImage])) {
				$shippedBackground = BackgroundService::SHIPPED_BACKGROUNDS[$backgroundImage];
				if ($this->isDarkVariant && isset($shippedBackground['dark_variant'])) {
					$backgroundImage = $shippedBackground['dark_variant'];
				}
				$variables['--image-background'] = "url('" . $this->urlGenerator->linkTo(Application::APP_ID, "img/background/$backgroundImage") . "')";
			}

			return $variables;
		}

		return [];
	}
}
