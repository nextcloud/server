<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Janis Köhr <janiskoehr@icloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Janis Köhr <janis.koehr@novatec-gmbh.de>
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
namespace OCA\Theming\Migration;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigrateUserConfig implements IRepairStep {

	protected IUserManager $userManager;
	protected IConfig $config;
	protected ThemesService $themesService;
	protected DarkTheme $darkTheme;
	protected DarkHighContrastTheme $darkHighContrastTheme;
	protected HighContrastTheme $highContrastTheme;
	protected DyslexiaFont $dyslexiaFont;

	/**
	 * MigrateUserConfig constructor.
	 */
	public function __construct(IConfig $config,
								IUserManager $userManager,
								ThemesService $themesService,
								DarkTheme $darkTheme,
								DarkHighContrastTheme $darkHighContrastTheme,
								HighContrastTheme $highContrastTheme,
								DyslexiaFont $dyslexiaFont) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->themesService = $themesService;

		$this->darkTheme = $darkTheme;
		$this->darkHighContrastTheme = $darkHighContrastTheme;
		$this->highContrastTheme = $highContrastTheme;
		$this->dyslexiaFont = $dyslexiaFont;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getName() {
		return 'Migrate old user accessibility config';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 * @since 25.0.0
	 */
	public function run(IOutput $output) {
		$output->startProgress();
		$this->userManager->callForSeenUsers(function (IUser $user) use ($output) {
			$config = [];

			$font = $this->config->getUserValue($user->getUID(), 'accessibility', 'font', false);
			$highcontrast = $this->config->getUserValue($user->getUID(), 'accessibility', 'highcontrast', false);
			$theme = $this->config->getUserValue($user->getUID(), 'accessibility', 'theme', false);

			if ($highcontrast || $theme) {
				if ($theme === 'dark' && $highcontrast === 'highcontrast') {
					$config[] = $this->darkHighContrastTheme->getId();
				} else if ($theme === 'dark') {
					$config[] = $this->darkTheme->getId();
				} else if ($highcontrast === 'highcontrast') {
					$config[] = $this->highContrastTheme->getId();
				}
			}
			
			if ($font === 'fontdyslexic') {
				$config[] = $this->dyslexiaFont->getId();
			}

			if (!empty($config)) {
				$this->config->setUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', json_encode(array_unique($config)));
			}

			$output->advance();
		});

		$this->config->deleteAppFromAllUsers('accessibility');

		$output->finishProgress();
	}
}
