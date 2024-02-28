<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Guillaume COMPAGNON <gcompagnon@outlook.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

	/** @var ThemingDefaults */
	protected $theming;

	/** @var Util */
	protected $util;

	/** @var IURLGenerator */
	protected $url;

	/** @var IConfig */
	protected $config;

	protected IUserSession $userSession;

	/**
	 * @param ThemingDefaults $theming
	 * @param Util $util
	 * @param IURLGenerator $url
	 * @param IConfig $config
	 */
	public function __construct(ThemingDefaults $theming, Util $util, IURLGenerator $url, IConfig $config, IUserSession $userSession) {
		$this->theming = $theming;
		$this->util = $util;
		$this->url = $url;
		$this->config = $config;
		$this->userSession = $userSession;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{
	 *     theming: array{
	 *         name: string,
	 *         url: string,
	 *         slogan: string,
	 *         color: string,
	 *         color-text: string,
	 *         color-element: string,
	 *         color-element-bright: string,
	 *         color-element-dark: string,
	 *         logo: string,
	 *         background: string,
	 *         background-plain: bool,
	 *         background-default: bool,
	 *         logoheader: string,
	 *         favicon: string,
	 *     },
	 * }
	 */
	public function getCapabilities() {
		$color = $this->theming->getDefaultColorPrimary();
		// Same as in DefaultTheme
		if ($color === BackgroundService::DEFAULT_COLOR) {
			$color = BackgroundService::DEFAULT_ACCESSIBLE_COLOR;
		}
		$colorText = $this->util->invertTextColor($color) ? '#000000' : '#ffffff';

		$backgroundLogo = $this->config->getAppValue('theming', 'backgroundMime', '');
		$backgroundPlain = $backgroundLogo === 'backgroundColor' || ($backgroundLogo === '' && $color !== '#0082c9');
		$background = $backgroundPlain ? $color : $this->url->getAbsoluteURL($this->theming->getBackground());

		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			/**
			 * Mimics the logic of generateUserBackgroundVariables() that generates the CSS variables.
			 * Also needs to be updated if the logic changes.
			 * @see \OCA\Theming\Themes\CommonThemeTrait::generateUserBackgroundVariables()
			 */
			$color = $this->theming->getColorPrimary();
			if ($color === BackgroundService::DEFAULT_COLOR) {
				$color = BackgroundService::DEFAULT_ACCESSIBLE_COLOR;
			}
			$colorText = $this->util->invertTextColor($color) ? '#000000' : '#ffffff';

			$backgroundImage = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT);
			if ($backgroundImage === BackgroundService::BACKGROUND_CUSTOM) {
				$backgroundPlain = false;
				$background = $this->url->linkToRouteAbsolute('theming.userTheme.getBackground');
			} elseif (isset(BackgroundService::SHIPPED_BACKGROUNDS[$backgroundImage])) {
				$backgroundPlain = false;
				$background = $this->url->linkTo(Application::APP_ID, "img/background/$backgroundImage");
			} elseif ($backgroundImage !== BackgroundService::BACKGROUND_DEFAULT) {
				$backgroundPlain = true;
				$background = $color;
			}
		}

		return [
			'theming' => [
				'name' => $this->theming->getName(),
				'url' => $this->theming->getBaseUrl(),
				'slogan' => $this->theming->getSlogan(),
				'color' => $color,
				'color-text' => $colorText,
				'color-element' => $this->util->elementColor($color),
				'color-element-bright' => $this->util->elementColor($color),
				'color-element-dark' => $this->util->elementColor($color, false),
				'logo' => $this->url->getAbsoluteURL($this->theming->getLogo()),
				'background' => $background,
				'background-plain' => $backgroundPlain,
				'background-default' => !$this->util->isBackgroundThemed(),
				'logoheader' => $this->url->getAbsoluteURL($this->theming->getLogo()),
				'favicon' => $this->url->getAbsoluteURL($this->theming->getLogo()),
			],
		];
	}
}
