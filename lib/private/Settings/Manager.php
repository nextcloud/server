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
use OCP\Encryption\IManager as EncryptionManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
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

	/**
	 * @param ILogger $log
	 * @param IDBConnection $dbc
	 * @param IL10N $l
	 * @param IConfig $config
	 * @param EncryptionManager $encryptionManager
	 * @param IUserManager $userManager
	 * @param ILockingProvider $lockingProvider
	 */
	public function __construct(
		ILogger $log,
		IDBConnection $dbc,
		IL10N $l,
		IConfig $config,
		EncryptionManager $encryptionManager,
		IUserManager $userManager,
		ILockingProvider $lockingProvider
	) {
		$this->log = $log;
		$this->dbc = $dbc;
		$this->l = $l;
		$this->config = $config;
		$this->encryptionManager = $encryptionManager;
		$this->userManager = $userManager;
		$this->lockingProvider = $lockingProvider;
	}

	/**
	 * @inheritdoc
	 */
	public function setupSettings(array $settings) {
		if(isset($settings[IManager::KEY_ADMIN_SECTION])) {
			$this->setupAdminSection($settings[IManager::KEY_ADMIN_SECTION]);
		}
		if(isset($settings[IManager::KEY_ADMIN_SETTINGS])) {
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

		if(isset($appInfo['settings'][IManager::KEY_ADMIN_SECTION])) {
			$this->remove(self::TABLE_ADMIN_SECTIONS, trim($appInfo['settings'][IManager::KEY_ADMIN_SECTION], '\\'));
		}
		if(isset($appInfo['settings'][IManager::KEY_ADMIN_SETTINGS])) {
			$this->remove(self::TABLE_ADMIN_SETTINGS, trim($appInfo['settings'][IManager::KEY_ADMIN_SETTINGS], '\\'));
		}
	}

	public function checkForOrphanedClassNames() {
		$tables = [ self::TABLE_ADMIN_SECTIONS, self::TABLE_ADMIN_SETTINGS ];
		foreach ($tables as $table) {
			$classes = $this->getClasses($table);
			foreach($classes as $className) {
				try {
					\OC::$server->query($className);
				} catch (QueryException $e) {
					$this->remove($table, $className);
				}
			}
		}
	}

	/**
	 * returns the registerd classes in the given table
	 *
	 * @param $table
	 * @return string[]
	 */
	private function getClasses($table) {
		$q = $this->dbc->getQueryBuilder();
		$resultStatement = $q->select('class')
			->from($table)
			->execute();
		$data = $resultStatement->fetchAll();
		$resultStatement->closeCursor();

		return array_map(function($row) { return $row['class']; }, $data);
	}

	/**
	 * @param string $sectionClassName
	 */
	private function setupAdminSection($sectionClassName) {
		if(!class_exists($sectionClassName)) {
			$this->log->debug('Could not find admin section class ' . $sectionClassName);
			return;
		}
		try {
			$section = $this->query($sectionClassName);
		} catch (QueryException $e) {
			// cancel
			return;
		}

		if(!$section instanceof ISection) {
			$this->log->error(
				'Admin section instance must implement \OCP\ISection. Invalid class: {class}',
				['class' => $sectionClassName]
			);
			return;
		}
		if(!$this->hasAdminSection(get_class($section))) {
			$this->addAdminSection($section);
		} else {
			$this->updateAdminSection($section);
		}
	}

	private function addAdminSection(ISection $section) {
		$this->add(self::TABLE_ADMIN_SECTIONS, [
			'id' => $section->getID(),
			'class' => get_class($section),
			'priority' => $section->getPriority(),
		]);
	}

	private function addAdminSettings(ISettings $settings) {
		$this->add(self::TABLE_ADMIN_SETTINGS, [
			'class' => get_class($settings),
			'section' => $settings->getSection(),
			'priority' => $settings->getPriority(),
		]);
	}

	/**
	 * @param string $table
	 * @param array $values
	 */
	private function add($table, array $values) {
		$query = $this->dbc->getQueryBuilder();
		$values = array_map(function($value) use ($query) {
			return $query->createNamedParameter($value);
		}, $values);
		$query->insert($table)->values($values);
		$query->execute();
	}

	private function updateAdminSettings(ISettings $settings) {
		$this->update(
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
		$this->update(
			self::TABLE_ADMIN_SECTIONS,
			'class',
			get_class($section),
			[
				'id'       => $section->getID(),
				'priority' => $section->getPriority(),
			]
		);
	}

	private function update($table, $idCol, $id, $values) {
		$query = $this->dbc->getQueryBuilder();
		$query->update($table);
		foreach($values as $key => $value) {
			$query->set($key, $query->createNamedParameter($value));
		}
		$query
			->where($query->expr()->eq($idCol, $query->createParameter($idCol)))
			->setParameter($idCol, $id)
			->execute();
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	private function hasAdminSection($className) {
		return $this->has(self::TABLE_ADMIN_SECTIONS, $className);
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	private function hasAdminSettings($className) {
		return $this->has(self::TABLE_ADMIN_SETTINGS, $className);
	}

	/**
	 * @param string $table
	 * @param string $className
	 * @return bool
	 */
	private function has($table, $className) {
		$query = $this->dbc->getQueryBuilder();
		$query->select('class')
			->from($table)
			->where($query->expr()->eq('class', $query->createNamedParameter($className)))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		return (bool) $row;
	}

	/**
	 * deletes an settings or admin entry from the given table
	 *
	 * @param $table
	 * @param $className
	 */
	private function remove($table, $className) {
		$query = $this->dbc->getQueryBuilder();
		$query->delete($table)
			->where($query->expr()->eq('class', $query->createNamedParameter($className)));

		$query->execute();
	}

	private function setupAdminSettings($settingsClassName) {
		if(!class_exists($settingsClassName)) {
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

		if(!$settings instanceof ISettings) {
			$this->log->error(
				'Admin section instance must implement \OCP\Settings\ISection. Invalid class: {class}',
				['class' => $settingsClassName]
			);
			return;
		}
		if(!$this->hasAdminSettings(get_class($settings))) {
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
			 0 => [new Section('server',        $this->l->t('Server settings'), 0)],
			 5 => [new Section('sharing',       $this->l->t('Sharing'), 0)],
			45 => [new Section('encryption',    $this->l->t('Encryption'), 0)],
			90 => [new Section('logging',       $this->l->t('Logging'), 0)],
			98 => [new Section('additional',    $this->l->t('Additional settings'), 0)],
			99 => [new Section('tips-tricks',   $this->l->t('Tips & tricks'), 0)],
		];

		$query = $this->dbc->getQueryBuilder();
		$query->selectDistinct('s.class')
			->addSelect('s.priority')
			->from(self::TABLE_ADMIN_SECTIONS, 's')
			->from(self::TABLE_ADMIN_SETTINGS, 'f')
			->where($query->expr()->eq('s.id', 'f.section'))
		;
		$result = $query->execute();

		while($row = $result->fetch()) {
			if(!isset($sections[$row['priority']])) {
				$sections[$row['priority']] = [];
			}
			try {
				$sections[$row['priority']][] = $this->query($row['class']);
			} catch (QueryException $e) {
				// skip
			}
		}
		$result->closeCursor();

		ksort($sections);
		return $sections;
	}

	private function getBuiltInAdminSettings($section) {
		$forms = [];
		try {
			if($section === 'server') {
				/** @var ISettings $form */
				$form = new Admin\Server($this->dbc, $this->config, $this->lockingProvider, $this->l);
				$forms[$form->getPriority()] = [$form];
			}
			if($section === 'encryption') {
				/** @var ISettings $form */
				$form = new Admin\Encryption($this->encryptionManager, $this->userManager);
				$forms[$form->getPriority()] = [$form];
			}
			if($section === 'sharing') {
				/** @var ISettings $form */
				$form = new Admin\Sharing($this->config);
				$forms[$form->getPriority()] = [$form];
			}
			if($section === 'logging') {
				/** @var ISettings $form */
				$form = new Admin\Logging($this->config);
				$forms[$form->getPriority()] = [$form];
			}
			if($section === 'additional') {
				/** @var ISettings $form */
				$form = new Admin\Additional($this->config);
				$forms[$form->getPriority()] = [$form];
			}
			if($section === 'tips-tricks') {
				/** @var ISettings $form */
				$form = new Admin\TipsTricks($this->config);
				$forms[$form->getPriority()] = [$form];
			}
		} catch (QueryException $e) {
			// skip
		}
		return $forms;
	}

	private function getAdminSettingsFromDB($section, &$settings) {
		$query = $this->dbc->getQueryBuilder();
		$query->select(['class', 'priority'])
			->from(self::TABLE_ADMIN_SETTINGS)
			->where($query->expr()->eq('section', $this->dbc->getQueryBuilder()->createParameter('section')))
			->setParameter('section', $section);

		$result = $query->execute();
		while($row = $result->fetch()) {
			if(!isset($settings[$row['priority']])) {
				$settings[$row['priority']] = [];
			}
			try {
				$settings[$row['priority']][] = $this->query($row['class']);
			} catch (QueryException $e) {
				// skip
			}
		}
		$result->closeCursor();

		ksort($settings);
	}

	/**
	 * @inheritdoc
	 */
	public function getAdminSettings($section) {
		$settings = $this->getBuiltInAdminSettings($section);
		$this->getAdminSettingsFromDB($section, $settings);
		return $settings;
	}
}
