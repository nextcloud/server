<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Settings;

use OC\Accounts\AccountManager;
use OCP\App\IAppManager;
use OCP\AppFramework\QueryException;
use OCP\Encryption\IManager as EncryptionManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Lock\ILockingProvider;
use OCP\Settings\ISettings;
use OCP\Settings\IManager;
use OCP\Settings\ISection;

class Manager implements IManager {
	/** @var ILogger */
	private $log;
	/** @var IDBConnection */
	private $dbc;
	/** @var Mapper */
	private $mapper;
	/** @var IL10N */
	private $l;
	/** @var IConfig */
	private $config;
	/** @var EncryptionManager */
	private $encryptionManager;
	/** @var IUserManager */
	private $userManager;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IRequest */
	private $request;
	/** @var IURLGenerator */
	private $url;
	/** @var AccountManager */
	private $accountManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IFactory */
	private $l10nFactory;
	/** @var \OC_Defaults */
	private $defaults;
	/** @var IAppManager */
	private $appManager;

	/**
	 * @param ILogger $log
	 * @param IDBConnection $dbc
	 * @param IL10N $l
	 * @param IConfig $config
	 * @param EncryptionManager $encryptionManager
	 * @param IUserManager $userManager
	 * @param ILockingProvider $lockingProvider
	 * @param IRequest $request
	 * @param Mapper $mapper
	 * @param IURLGenerator $url
	 * @param AccountManager $accountManager
	 * @param IGroupManager $groupManager
	 * @param IFactory $l10nFactory
	 * @param \OC_Defaults $defaults
	 */
	public function __construct(
		ILogger $log,
		IDBConnection $dbc,
		IL10N $l,
		IConfig $config,
		EncryptionManager $encryptionManager,
		IUserManager $userManager,
		ILockingProvider $lockingProvider,
		IRequest $request,
		Mapper $mapper,
		IURLGenerator $url,
		AccountManager $accountManager,
		IGroupManager $groupManager,
		IFactory $l10nFactory,
		\OC_Defaults $defaults,
		IAppManager $appManager
	) {
		$this->log = $log;
		$this->dbc = $dbc;
		$this->mapper = $mapper;
		$this->l = $l;
		$this->config = $config;
		$this->encryptionManager = $encryptionManager;
		$this->userManager = $userManager;
		$this->lockingProvider = $lockingProvider;
		$this->request = $request;
		$this->url = $url;
		$this->accountManager = $accountManager;
		$this->groupManager = $groupManager;
		$this->l10nFactory = $l10nFactory;
		$this->defaults = $defaults;
		$this->appManager = $appManager;
	}

	/**
	 * @inheritdoc
	 */
	public function setupSettings(array $settings) {
		if (isset($settings[IManager::KEY_ADMIN_SECTION])) {
			$this->setupSectionEntry($settings[IManager::KEY_ADMIN_SECTION], 'admin');
		}
		if (isset($settings[IManager::KEY_ADMIN_SETTINGS])) {
			$this->setupSettingsEntry($settings[IManager::KEY_ADMIN_SETTINGS], 'admin');
		}

		if (isset($settings[IManager::KEY_PERSONAL_SECTION])) {
			$this->setupSectionEntry($settings[IManager::KEY_PERSONAL_SECTION], 'personal');
		}
		if (isset($settings[IManager::KEY_PERSONAL_SETTINGS])) {
			$this->setupSettingsEntry($settings[IManager::KEY_PERSONAL_SETTINGS], 'personal');
		}
	}

	/**
	 * attempts to remove an apps section and/or settings entry. A listener is
	 * added centrally making sure that this method is called ones an app was
	 * disabled.
	 *
	 * @param string $appId
	 * @since 9.1.0
	 */
	public function onAppDisabled($appId) {
		$appInfo = \OC_App::getAppInfo($appId); // hello static legacy

		if (isset($appInfo['settings'][IManager::KEY_ADMIN_SECTION])) {
			$this->mapper->remove(Mapper::TABLE_ADMIN_SECTIONS, trim($appInfo['settings'][IManager::KEY_ADMIN_SECTION], '\\'));
		}
		if (isset($appInfo['settings'][IManager::KEY_ADMIN_SETTINGS])) {
			$this->mapper->remove(Mapper::TABLE_ADMIN_SETTINGS, trim($appInfo['settings'][IManager::KEY_ADMIN_SETTINGS], '\\'));
		}

		if (isset($appInfo['settings'][IManager::KEY_PERSONAL_SECTION])) {
			$this->mapper->remove(Mapper::TABLE_PERSONAL_SECTIONS, trim($appInfo['settings'][IManager::KEY_PERSONAL_SECTION], '\\'));
		}
		if (isset($appInfo['settings'][IManager::KEY_PERSONAL_SETTINGS])) {
			$this->mapper->remove(Mapper::TABLE_PERSONAL_SETTINGS, trim($appInfo['settings'][IManager::KEY_PERSONAL_SETTINGS], '\\'));
		}
	}

	public function checkForOrphanedClassNames() {
		$tables = [Mapper::TABLE_ADMIN_SECTIONS, Mapper::TABLE_ADMIN_SETTINGS, Mapper::TABLE_PERSONAL_SECTIONS, Mapper::TABLE_PERSONAL_SETTINGS];
		foreach ($tables as $table) {
			$classes = $this->mapper->getClasses($table);
			foreach ($classes as $className) {
				try {
					\OC::$server->query($className);
				} catch (QueryException $e) {
					$this->mapper->remove($table, $className);
				}
			}
		}
	}

	/**
	 * @param string $sectionClassName
	 * @param string $type either 'admin' or 'personal'
	 */
	private function setupSectionEntry($sectionClassName, $type) {
		if (!class_exists($sectionClassName)) {
			$this->log->debug('Could not find ' . ucfirst($type) . ' section class ' . $sectionClassName);
			return;
		}
		try {
			$section = $this->query($sectionClassName);
		} catch (QueryException $e) {
			// cancel
			return;
		}

		if (!$section instanceof ISection) {
			$this->log->error(
				ucfirst($type) .' section instance must implement \OCP\ISection. Invalid class: {class}',
				['class' => $sectionClassName]
			);
			return;
		}
		$table = $this->getSectionTableForType($type);
		if(!$this->hasSection(get_class($section), $table)) {
			$this->addSection($section, $table);
		} else {
			$this->updateSection($section, $table);
		}
	}

	private function addSection(ISection $section, $table) {
		$this->mapper->add($table, [
			'id' => $section->getID(),
			'class' => get_class($section),
			'priority' => $section->getPriority(),
		]);
	}

	private function addSettings(ISettings $settings, $table) {
		$this->mapper->add($table, [
			'class' => get_class($settings),
			'section' => $settings->getSection(),
			'priority' => $settings->getPriority(),
		]);
	}

	private function updateSettings(ISettings $settings, $table) {
		$this->mapper->update(
			$table,
			'class',
			get_class($settings),
			[
				'section' => $settings->getSection(),
				'priority' => $settings->getPriority(),
			]
		);
	}

	private function updateSection(ISection $section, $table) {
		$this->mapper->update(
			$table,
			'class',
			get_class($section),
			[
				'id' => $section->getID(),
				'priority' => $section->getPriority(),
			]
		);
	}

	/**
	 * @param string $className
	 * @param string $table
	 * @return bool
	 */
	private function hasSection($className, $table) {
		return $this->mapper->has($table, $className);
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	private function hasSettings($className, $table) {
		return $this->mapper->has($table, $className);
	}

	private function setupSettingsEntry($settingsClassName, $type) {
		if (!class_exists($settingsClassName)) {
			$this->log->debug('Could not find ' . $type . ' section class ' . $settingsClassName);
			return;
		}

		try {
			/** @var ISettings $settings */
			$settings = $this->query($settingsClassName);
		} catch (QueryException $e) {
			// cancel
			return;
		}

		if (!$settings instanceof ISettings) {
			$this->log->error(
				ucfirst($type) . ' section instance must implement \OCP\Settings\ISettings. Invalid class: {class}',
				['class' => $settingsClassName]
			);
			return;
		}
		$table = $this->getSettingsTableForType($type);
		if (!$this->hasSettings(get_class($settings), $table)) {
			$this->addSettings($settings, $table);
		} else {
			$this->updateSettings($settings, $table);
		}
	}

	private function getSectionTableForType($type) {
		if($type === 'admin') {
			return Mapper::TABLE_ADMIN_SECTIONS;
		} else if($type === 'personal') {
			return Mapper::TABLE_PERSONAL_SECTIONS;
		}
		throw new \InvalidArgumentException('"admin" or "personal" expected');
	}

	private function getSettingsTableForType($type) {
		if($type === 'admin') {
			return Mapper::TABLE_ADMIN_SETTINGS;
		} else if($type === 'personal') {
			return Mapper::TABLE_PERSONAL_SETTINGS;
		}
		throw new \InvalidArgumentException('"admin" or "personal" expected');
	}

	private function query($className) {
		try {
			return \OC::$server->query($className);
		} catch (QueryException $e) {
			$this->log->logException($e);
			throw $e;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getAdminSections() {
		// built-in sections
		$sections = [
			0 => [new Section('server', $this->l->t('Basic settings'), 0, $this->url->imagePath('settings', 'admin.svg'))],
			5 => [new Section('sharing', $this->l->t('Sharing'), 0, $this->url->imagePath('core', 'actions/share.svg'))],
			10 => [new Section('security', $this->l->t('Security'), 0, $this->url->imagePath('core', 'actions/password.svg'))],
			45 => [new Section('encryption', $this->l->t('Encryption'), 0, $this->url->imagePath('core', 'actions/password.svg'))],
			98 => [new Section('additional', $this->l->t('Additional settings'), 0, $this->url->imagePath('core', 'actions/settings-dark.svg'))],
			99 => [new Section('tips-tricks', $this->l->t('Tips & tricks'), 0, $this->url->imagePath('settings', 'help.svg'))],
		];

		$rows = $this->mapper->getAdminSectionsFromDB();

		foreach ($rows as $row) {
			if (!isset($sections[$row['priority']])) {
				$sections[$row['priority']] = [];
			}
			try {
				$sections[$row['priority']][] = $this->query($row['class']);
			} catch (QueryException $e) {
				// skip
			}
		}

		ksort($sections);

		return $sections;
	}

	/**
	 * @param string $section
	 * @return ISection[]
	 */
	private function getBuiltInAdminSettings($section) {
		$forms = [];
		try {
			if ($section === 'server') {
				/** @var ISettings $form */
				$form = new Admin\Server($this->dbc, $this->request, $this->config, $this->lockingProvider, $this->l);
				$forms[$form->getPriority()] = [$form];
				$form = new Admin\ServerDevNotice();
				$forms[$form->getPriority()] = [$form];
			}
			if ($section === 'encryption') {
				/** @var ISettings $form */
				$form = new Admin\Encryption($this->encryptionManager, $this->userManager);
				$forms[$form->getPriority()] = [$form];
			}
			if ($section === 'sharing') {
				/** @var ISettings $form */
				$form = new Admin\Sharing($this->config);
				$forms[$form->getPriority()] = [$form];
			}
			if ($section === 'additional') {
				/** @var ISettings $form */
				$form = new Admin\Additional($this->config);
				$forms[$form->getPriority()] = [$form];
			}
			if ($section === 'tips-tricks') {
				/** @var ISettings $form */
				$form = new Admin\TipsTricks($this->config);
				$forms[$form->getPriority()] = [$form];
			}
		} catch (QueryException $e) {
			// skip
		}
		return $forms;
	}

	/**
	 * @param string $section
	 * @return ISection[]
	 */
	private function getBuiltInPersonalSettings($section) {
		$forms = [];
		try {
			if ($section === 'personal-info') {
				/** @var ISettings $form */
				$form = new Personal\PersonalInfo(
					$this->config,
					$this->userManager,
					$this->groupManager,
					$this->accountManager,
					$this->appManager,
					$this->l10nFactory,
					$this->l
				);
				$forms[$form->getPriority()] = [$form];
			}
			if($section === 'security') {
				/** @var ISettings $form */
				$form = new Personal\Security();
				$forms[$form->getPriority()] = [$form];
			}
			if ($section === 'additional') {
				/** @var ISettings $form */
				$form = new Personal\Additional($this->config);
				$forms[$form->getPriority()] = [$form];
			}
		} catch (QueryException $e) {
			// skip
		}
		return $forms;
	}

	/**
	 * @inheritdoc
	 */
	public function getAdminSettings($section) {
		$settings = $this->getBuiltInAdminSettings($section);
		$dbRows = $this->mapper->getAdminSettingsFromDB($section);

		foreach ($dbRows as $row) {
			if (!isset($settings[$row['priority']])) {
				$settings[$row['priority']] = [];
			}
			try {
				$settings[$row['priority']][] = $this->query($row['class']);
			} catch (QueryException $e) {
				// skip
			}
		}

		ksort($settings);
		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getPersonalSections() {
		$sections = [
			0 => [new Section('personal-info', $this->l->t('Personal info'), 0, $this->url->imagePath('core', 'actions/info.svg'))],
			5 => [new Section('security', $this->l->t('Security'), 0, $this->url->imagePath('settings', 'password.svg'))],
			15 => [new Section('sync-clients', $this->l->t('Sync clients'), 0, $this->url->imagePath('settings', 'change.svg'))],
		];

		$legacyForms = \OC_App::getForms('personal');
		if(count($legacyForms) > 0 && $this->hasLegacyPersonalSettingsToRender($legacyForms)) {
			$sections[98] = [new Section('additional', $this->l->t('Additional settings'), 0, $this->url->imagePath('core', 'actions/settings-dark.svg'))];
		}

		$rows = $this->mapper->getPersonalSectionsFromDB();

		foreach ($rows as $row) {
			if (!isset($sections[$row['priority']])) {
				$sections[$row['priority']] = [];
			}
			try {
				$sections[$row['priority']][] = $this->query($row['class']);
			} catch (QueryException $e) {
				// skip
			}
		}

		ksort($sections);

		return $sections;
	}

	/**
	 * @param $forms
	 * @return bool
	 */
	private function hasLegacyPersonalSettingsToRender($forms) {
		foreach ($forms as $form) {
			if(trim($form) !== '') {
				return true;
			}
		}
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getPersonalSettings($section) {
		$settings = $this->getBuiltInPersonalSettings($section);
		$dbRows = $this->mapper->getPersonalSettingsFromDB($section);

		foreach ($dbRows as $row) {
			if (!isset($settings[$row['priority']])) {
				$settings[$row['priority']] = [];
			}
			try {
				$settings[$row['priority']][] = $this->query($row['class']);
			} catch (QueryException $e) {
				// skip
			}
		}

		ksort($settings);
		return $settings;
	}
}
