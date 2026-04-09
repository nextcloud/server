<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\IIconSection;
use OCP\Settings\IManager;
use OCP\Settings\ISettings;
use OCP\Settings\ISubAdminSettings;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	private ?IL10N $l = null;

	/** @var array<self::SETTINGS_*, list<class-string<IIconSection>>> */
	protected array $sectionClasses = [];

	/** @var array<self::SETTINGS_*, array<string, IIconSection>> */
	protected array $sections = [];

	/** @var array<class-string<ISettings>, self::SETTINGS_*> */
	protected array $settingClasses = [];

	/** @var array<self::SETTINGS_*, array<string, list<ISettings>>> */
	protected array $settings = [];

	public function __construct(
		private LoggerInterface $log,
		private IFactory $l10nFactory,
		private IURLGenerator $url,
		private IServerContainer $container,
		private AuthorizedGroupMapper $mapper,
		private IGroupManager $groupManager,
		private ISubAdmin $subAdmin,
	) {
	}

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
	protected function getSettings(string $type, string $section, ?Closure $filter = null): array {
		if (!isset($this->settings[$type])) {
			$this->settings[$type] = [];
		}
		if (!isset($this->settings[$type][$section])) {
			$this->settings[$type][$section] = [];

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
				$settingSection = $setting->getSection();
				if ($settingSection === null) {
					continue;
				}

				if (!isset($this->settings[$settingsType][$settingSection])) {
					$this->settings[$settingsType][$settingSection] = [];
				}
				$this->settings[$settingsType][$settingSection][] = $setting;

				unset($this->settingClasses[$class]);
			}
		}

		if ($filter !== null) {
			return array_values(array_filter($this->settings[$type][$section], $filter));
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

		if (count($this->getPersonalSettings('additional')) > 1) {
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

	/**
	 * @return array<string, array{section:IIconSection,settings:list<IDelegatedSettings>}>
	 */
	public function getAdminDelegatedSettings(): array {
		$sections = $this->getAdminSections();
		$settings = [];
		foreach ($sections as $sectionPriority) {
			foreach ($sectionPriority as $section) {
				/** @var IDelegatedSettings[] */
				$sectionSettings = array_merge(
					$this->getSettings(self::SETTINGS_ADMIN, $section->getID(), fn (ISettings $settings): bool => $settings instanceof IDelegatedSettings),
					$this->getSettings(self::SETTINGS_DELEGATION, $section->getID(), fn (ISettings $settings): bool => $settings instanceof IDelegatedSettings),
				);
				usort(
					$sectionSettings,
					fn (ISettings $s1, ISettings $s2) => $s1->getPriority() <=> $s2->getPriority()
				);
				$settings[$section->getID()] = [
					'section' => $section,
					'settings' => $sectionSettings,
				];
			}
		}
		uasort($settings, fn (array $a, array $b) => $a['section']->getPriority() <=> $b['section']->getPriority());
		return $settings;
	}
}
