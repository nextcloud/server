<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author sualko <klaus@jsxc.org>
 * @author Carl Schwan <carl@carlschwan.eu>
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

namespace OC\Settings;

use Closure;
use OCP\AppFramework\QueryException;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Settings\IIconSection;
use OCP\Settings\IManager;
use OCP\Settings\ISettings;
use OCP\Settings\ISubAdminSettings;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	/** @var LoggerInterface */
	private $log;

	/** @var IL10N */
	private $l;

	/** @var IFactory */
	private $l10nFactory;

	/** @var IURLGenerator */
	private $url;

	/** @var IServerContainer */
	private $container;

	/** @var AuthorizedGroupMapper $mapper */
	private $mapper;

	/** @var IGroupManager $groupManager */
	private $groupManager;

	/** @var ISubAdmin $subAdmin */
	private $subAdmin;

	public function __construct(
		LoggerInterface $log,
		IFactory $l10nFactory,
		IURLGenerator $url,
		IServerContainer $container,
		AuthorizedGroupMapper $mapper,
		IGroupManager $groupManager,
		ISubAdmin $subAdmin
	) {
		$this->log = $log;
		$this->l10nFactory = $l10nFactory;
		$this->url = $url;
		$this->container = $container;
		$this->mapper = $mapper;
		$this->groupManager = $groupManager;
		$this->subAdmin = $subAdmin;
	}

	/** @var array<self::SETTINGS_*, list<class-string<IIconSection>>> */
	protected $sectionClasses = [];

	/** @var array<self::SETTINGS_*, array<string, IIconSection>> */
	protected $sections = [];

	/**
	 * @inheritdoc
	 */
	public function registerSection(string $type, string $section) {
		if (!isset($this->sectionClasses[$type])) {
			$this->sectionClasses[$type] = [];
		}

		$this->sectionClasses[$type][] = $section;
	}

	/**
	 * @psalm-param self::SETTINGS_* $type
	 *
	 * @return IIconSection[]
	 */
	protected function getSections(string $type): array {
		if (!isset($this->sections[$type])) {
			$this->sections[$type] = [];
		}

		if (!isset($this->sectionClasses[$type])) {
			return $this->sections[$type];
		}

		foreach (array_unique($this->sectionClasses[$type]) as $index => $class) {
			try {
				/** @var IIconSection $section */
				$section = $this->container->get($class);
			} catch (QueryException $e) {
				$this->log->info($e->getMessage(), ['exception' => $e]);
				continue;
			}

			$sectionID = $section->getID();

			if (!$this->isKnownDuplicateSectionId($sectionID) && isset($this->sections[$type][$sectionID])) {
				$e = new \InvalidArgumentException('Section with the same ID already registered: ' . $sectionID . ', class: ' . $class);
				$this->log->info($e->getMessage(), ['exception' => $e]);
				continue;
			}

			$this->sections[$type][$sectionID] = $section;

			unset($this->sectionClasses[$type][$index]);
		}

		return $this->sections[$type];
	}

	/**
	 * @inheritdoc
	 */
	public function getSection(string $type, string $sectionId): ?IIconSection {
		if (isset($this->sections[$type]) && isset($this->sections[$type][$sectionId])) {
			return $this->sections[$type][$sectionId];
		}
		return null;
	}

	protected function isKnownDuplicateSectionId(string $sectionID): bool {
		return in_array($sectionID, [
			'connected-accounts',
			'notifications',
		], true);
	}

	/** @var array<class-string<ISettings>, self::SETTINGS_*> */
	protected $settingClasses = [];

	/** @var array<self::SETTINGS_*, array<string, list<ISettings>>> */
	protected $settings = [];

	/**
	 * @inheritdoc
	 */
	public function registerSetting(string $type, string $setting) {
		$this->settingClasses[$setting] = $type;
	}

	/**
	 * @psalm-param self::SETTINGS_* $type The type of the setting.
	 * @param string $section
	 * @param ?Closure $filter optional filter to apply on all loaded ISettings
	 *
	 * @return ISettings[]
	 */
	protected function getSettings(string $type, string $section, Closure $filter = null): array {
		if (!isset($this->settings[$type])) {
			$this->settings[$type] = [];
		}
		if (!isset($this->settings[$type][$section])) {
			$this->settings[$type][$section] = [];
		}

		foreach ($this->settingClasses as $class => $settingsType) {
			if ($type !== $settingsType) {
				continue;
			}

			try {
				/** @var ISettings $setting */
				$setting = $this->container->get($class);
			} catch (QueryException $e) {
				$this->log->info($e->getMessage(), ['exception' => $e]);
				continue;
			}

			if (!$setting instanceof ISettings) {
				$e = new \InvalidArgumentException('Invalid settings setting registered (' . $class . ')');
				$this->log->info($e->getMessage(), ['exception' => $e]);
				continue;
			}

			if ($filter !== null && !$filter($setting)) {
				continue;
			}
			if ($setting->getSection() === null) {
				continue;
			}

			if (!isset($this->settings[$settingsType][$setting->getSection()])) {
				$this->settings[$settingsType][$setting->getSection()] = [];
			}
			$this->settings[$settingsType][$setting->getSection()][] = $setting;

			unset($this->settingClasses[$class]);
		}

		return $this->settings[$type][$section];
	}

	/**
	 * @inheritdoc
	 */
	public function getAdminSections(): array {
		// built-in sections
		$sections = [];

		$appSections = $this->getSections('admin');

		foreach ($appSections as $section) {
			/** @var IIconSection $section */
			if (!isset($sections[$section->getPriority()])) {
				$sections[$section->getPriority()] = [];
			}

			$sections[$section->getPriority()][] = $section;
		}

		ksort($sections);

		return $sections;
	}

	/**
	 * @inheritdoc
	 */
	public function getAdminSettings(string $section, bool $subAdminOnly = false): array {
		if ($subAdminOnly) {
			$subAdminSettingsFilter = function (ISettings $settings) {
				return $settings instanceof ISubAdminSettings;
			};
			$appSettings = $this->getSettings('admin', $section, $subAdminSettingsFilter);
		} else {
			$appSettings = $this->getSettings('admin', $section);
		}

		$settings = [];
		foreach ($appSettings as $setting) {
			if (!isset($settings[$setting->getPriority()])) {
				$settings[$setting->getPriority()] = [];
			}
			$settings[$setting->getPriority()][] = $setting;
		}

		ksort($settings);
		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getPersonalSections(): array {
		if ($this->l === null) {
			$this->l = $this->l10nFactory->get('lib');
		}

		$sections = [];

		$legacyForms = \OC_App::getForms('personal');
		if ((!empty($legacyForms) && $this->hasLegacyPersonalSettingsToRender($legacyForms))
			|| count($this->getPersonalSettings('additional')) > 1) {
			$sections[98] = [new Section('additional', $this->l->t('Additional settings'), 0, $this->url->imagePath('core', 'actions/settings-dark.svg'))];
		}

		$appSections = $this->getSections('personal');

		foreach ($appSections as $section) {
			/** @var IIconSection $section */
			if (!isset($sections[$section->getPriority()])) {
				$sections[$section->getPriority()] = [];
			}

			$sections[$section->getPriority()][] = $section;
		}

		ksort($sections);

		return $sections;
	}

	/**
	 * @param string[] $forms
	 *
	 * @return bool
	 */
	private function hasLegacyPersonalSettingsToRender(array $forms): bool {
		foreach ($forms as $form) {
			if (trim($form) !== '') {
				return true;
			}
		}
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getPersonalSettings(string $section): array {
		$settings = [];
		$appSettings = $this->getSettings('personal', $section);

		foreach ($appSettings as $setting) {
			if (!isset($settings[$setting->getPriority()])) {
				$settings[$setting->getPriority()] = [];
			}
			$settings[$setting->getPriority()][] = $setting;
		}

		ksort($settings);
		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getAllowedAdminSettings(string $section, IUser $user): array {
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		if ($isAdmin) {
			$appSettings = $this->getSettings('admin', $section);
		} else {
			$authorizedSettingsClasses = $this->mapper->findAllClassesForUser($user);
			if ($this->subAdmin->isSubAdmin($user)) {
				$authorizedGroupFilter = function (ISettings $settings) use ($authorizedSettingsClasses) {
					return $settings instanceof ISubAdminSettings
						|| in_array(get_class($settings), $authorizedSettingsClasses) === true;
				};
			} else {
				$authorizedGroupFilter = function (ISettings $settings) use ($authorizedSettingsClasses) {
					return in_array(get_class($settings), $authorizedSettingsClasses) === true;
				};
			}
			$appSettings = $this->getSettings('admin', $section, $authorizedGroupFilter);
		}

		$settings = [];
		foreach ($appSettings as $setting) {
			if (!isset($settings[$setting->getPriority()])) {
				$settings[$setting->getPriority()] = [];
			}
			$settings[$setting->getPriority()][] = $setting;
		}

		ksort($settings);
		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getAllAllowedAdminSettings(IUser $user): array {
		$this->getSettings('admin', ''); // Make sure all the settings are loaded
		$settings = [];
		$authorizedSettingsClasses = $this->mapper->findAllClassesForUser($user);
		foreach ($this->settings['admin'] as $section) {
			foreach ($section as $setting) {
				if (in_array(get_class($setting), $authorizedSettingsClasses) === true) {
					$settings[] = $setting;
				}
			}
		}
		return $settings;
	}
}
