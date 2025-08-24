<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;
use OCP\Capabilities\IPublicCapability;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Class Capabilities
 *
 * @package OCA\Theming
 */
class Capabilities implements IPublicCapability {

	/**
	 * @param ThemingDefaults $theming
	 * @param Util $util
	 * @param IURLGenerator $url
	 * @param IConfig $config
	 */
	public function __construct(
		protected ThemingDefaults $theming,
		protected Util $util,
		protected IURLGenerator $url,
		protected IConfig $config,
		protected IUserSession $userSession,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{
	 *     theming: array{
	 *         name: string,
	 *         productName: string,
	 *         url: string,
	 *         slogan: string,
	 *         color: string,
	 *         color-text: string,
	 *         color-element: string,
	 *         color-element-bright: string,
	 *         color-element-dark: string,
	 *         logo: string,
	 *         background: string,
	 *         background-text: string,
	 *         background-plain: bool,
	 *         background-default: bool,
	 *         logoheader: string,
	 *         favicon: string,
	 *     },
	 * }
	 */
	public function getCapabilities() {
		$color = $this->theming->getDefaultColorPrimary();
		$colorText = $this->util->invertTextColor($color) ? '#000000' : '#ffffff';

		$backgroundLogo = $this->config->getAppValue('theming', 'backgroundMime', '');
		$backgroundColor = $this->theming->getColorBackground();
		$backgroundText = $this->theming->getTextColorBackground();
		$backgroundPlain = $backgroundLogo === 'backgroundColor' || ($backgroundLogo === '' && $backgroundColor !== BackgroundService::DEFAULT_COLOR);
		$background = $backgroundPlain ? $backgroundColor : $this->url->getAbsoluteURL($this->theming->getBackground());

		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			/**
			 * Mimics the logic of generateUserBackgroundVariables() that generates the CSS variables.
			 * Also needs to be updated if the logic changes.
			 * @see \OCA\Theming\Themes\CommonThemeTrait::generateUserBackgroundVariables()
			 */
			$color = $this->theming->getColorPrimary();
			$colorText = $this->theming->getTextColorPrimary();

			$backgroundImage = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT);
			if ($backgroundImage === BackgroundService::BACKGROUND_CUSTOM) {
				$backgroundPlain = false;
				$background = $this->url->linkToRouteAbsolute('theming.userTheme.getBackground');
			} elseif (isset(BackgroundService::SHIPPED_BACKGROUNDS[$backgroundImage])) {
				$backgroundPlain = false;
				$background = $this->url->linkTo(Application::APP_ID, "img/background/$backgroundImage");
			} elseif ($backgroundImage !== BackgroundService::BACKGROUND_DEFAULT) {
				$backgroundPlain = true;
				$background = $backgroundColor;
			}
		}

		return [
			'theming' => [
				'name' => $this->theming->getName(),
				'productName' => $this->theming->getProductName(),
				'url' => $this->theming->getBaseUrl(),
				'slogan' => $this->theming->getSlogan(),
				'color' => $color,
				'color-text' => $colorText,
				'color-element' => $this->util->elementColor($color),
				'color-element-bright' => $this->util->elementColor($color),
				'color-element-dark' => $this->util->elementColor($color, false),
				'logo' => $this->url->getAbsoluteURL($this->theming->getLogo()),
				'background' => $background,
				'background-text' => $backgroundText,
				'background-plain' => $backgroundPlain,
				'background-default' => !$this->util->isBackgroundThemed(),
				'logoheader' => $this->url->getAbsoluteURL($this->theming->getLogo()),
				'favicon' => $this->url->getAbsoluteURL($this->theming->getLogo()),
			],
		];
	}
}
