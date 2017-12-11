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

use OCP\AppFramework\QueryException;
use OCP\AutoloadNotAllowedException;
use OCP\Encryption\IManager as EncryptionManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Settings\ISettings;
use OCP\Settings\IManager;
use OCP\Settings\ISection;

class Manager implements IManager {
	const TABLE_ADMIN_SETTINGS = 'admin_settings';
	const TABLE_ADMIN_SECTIONS = 'admin_sections';

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
		IURLGenerator $url
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
	}

	/**
	 * @inheritdoc
	 */
	public function setupSettings(array $settings) {
		if (isset($settings[IManager::KEY_ADMIN_SECTION])) {
			$this->setupAdminSection($settings[IManager::KEY_ADMIN_SECTION]);
		}
		if (isset($settings[IManager::KEY_ADMIN_SETTINGS])) {
			$this->setupAdminSettings($settings[IManager::KEY_ADMIN_SETTINGS]);
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
			$this->mapper->remove(self::TABLE_ADMIN_SECTIONS, trim($appInfo['settings'][IManager::KEY_ADMIN_SECTION], '\\'));
		}
		if (isset($appInfo['settings'][IManager::KEY_ADMIN_SETTINGS])) {
			$this->mapper->remove(self::TABLE_ADMIN_SETTINGS, trim($appInfo['settings'][IManager::KEY_ADMIN_SETTINGS], '\\'));
		}
	}

	public function checkForOrphanedClassNames() {
		$tables = [self::TABLE_ADMIN_SECTIONS, self::TABLE_ADMIN_SETTINGS];
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
	 */
	private function setupAdminSection($sectionClassName) {
		if (!class_exists($sectionClassName)) {
			$this->log->debug('Could not find admin section class ' . $sectionClassName);
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
				'Admin section instance must implement \OCP\ISection. Invalid class: {class}',
				['class' => $sectionClassName]
			);
			return;
		}
		if (!$this->hasAdminSection(get_class($section))) {
			$this->addAdminSection($section);
		} else {
			$this->updateAdminSection($section);
		}
	}

	private function addAdminSection(ISection $section) {
		$this->mapper->add(self::TABLE_ADMIN_SECTIONS, [
			'id' => $section->getID(),
			'class' => get_class($section),
			'priority' => $section->getPriority(),
		]);
	}

	private function addAdminSettings(ISettings $settings) {
		$this->mapper->add(self::TABLE_ADMIN_SETTINGS, [
			'class' => get_class($settings),
			'section' => $settings->getSection(),
			'priority' => $settings->getPriority(),
		]);
	}

	private function updateAdminSettings(ISettings $settings) {
		$this->mapper->update(
			self::TABLE_ADMIN_SETTINGS,
			'class',
			get_class($settings),
			[
				'section' => $settings->getSection(),
				'priority' => $settings->getPriority(),
			]
		);
	}

	private function updateAdminSection(ISection $section) {
		$this->mapper->update(
			self::TABLE_ADMIN_SECTIONS,
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
	 * @return bool
	 */
	private function hasAdminSection($className) {
		return $this->mapper->has(self::TABLE_ADMIN_SECTIONS, $className);
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	private function hasAdminSettings($className) {
		return $this->mapper->has(self::TABLE_ADMIN_SETTINGS, $className);
	}

	private function setupAdminSettings($settingsClassName) {
		if (!class_exists($settingsClassName)) {
			$this->log->debug('Could not find admin section class ' . $settingsClassName);
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
				'Admin section instance must implement \OCP\Settings\ISection. Invalid class: {class}',
				['class' => $settingsClassName]
			);
			return;
		}
		if (!$this->hasAdminSettings(get_class($settings))) {
			$this->addAdminSettings($settings);
		} else {
			$this->updateAdminSettings($settings);
		}
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
			} catch (AutoloadNotAllowedException $e) {
				// skip error and remove remnant of disabled app
				$this->log->warning('Orphan setting entry will be removed from admin_settings: ' . json_encode($row));
				$this->mapper->remove(Mapper::TABLE_ADMIN_SETTINGS, $row['class']);
			}
		}

		ksort($settings);
		return $settings;
	}
}
