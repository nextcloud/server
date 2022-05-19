<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Theming\Settings;

use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	private string $appName;
	private IConfig $config;
	private IL10N $l;
	private ThemingDefaults $themingDefaults;
	private IURLGenerator $urlGenerator;
	private ImageManager $imageManager;

	public function __construct(string $appName,
								IConfig $config,
								IL10N $l,
								ThemingDefaults $themingDefaults,
								IURLGenerator $urlGenerator,
								ImageManager $imageManager) {
		$this->appName = $appName;
		$this->config = $config;
		$this->l = $l;
		$this->themingDefaults = $themingDefaults;
		$this->urlGenerator = $urlGenerator;
		$this->imageManager = $imageManager;
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

		$parameters = [
			'themable' => $themable,
			'errorMessage' => $errorMessage,
			'name' => $this->themingDefaults->getEntity(),
			'url' => $this->themingDefaults->getBaseUrl(),
			'slogan' => $this->themingDefaults->getSlogan(),
			'color' => $this->themingDefaults->getColorPrimary(),
			'color_dark_theme' => $this->themingDefaults->getColorPrimary(true),
			'uploadLogoRoute' => $this->urlGenerator->linkToRoute('theming.Theming.uploadImage'),
			'canThemeIcons' => $this->imageManager->shouldReplaceIcons(),
			'iconDocs' => $this->urlGenerator->linkToDocs('admin-theming-icons'),
			'images' => $this->imageManager->getCustomImages(),
			'imprintUrl' => $this->themingDefaults->getImprintUrl(),
			'privacyUrl' => $this->themingDefaults->getPrivacyUrl(),
		];

		return new TemplateResponse($this->appName, 'settings-admin', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return $this->appName;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
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
