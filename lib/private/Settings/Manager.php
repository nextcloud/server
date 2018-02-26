<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Marius Blüm <marius@lineone.io>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
use OCP\Util;

class Manager implements IManager {
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
	 * @param IURLGenerator $url
	 * @param AccountManager $accountManager
	 * @param IGroupManager $groupManager
	 * @param IFactory $l10nFactory
	 * @param IAppManager $appManager
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
		IURLGenerator $url,
		AccountManager $accountManager,
		IGroupManager $groupManager,
		IFactory $l10nFactory,
		IAppManager $appManager
	) {
		$this->log = $log;
		$this->dbc = $dbc;
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
		$this->appManager = $appManager;
	}

	/** @var array */
	protected $sectionClasses = [];

	/** @var array */
	protected $sections = [];

	/**
	 * @param string $type 'admin' or 'personal'
	 * @param string $section Class must implement OCP\Settings\ISection
	 * @return void
	 */
	public function registerSection(string $type, string $section) {
		$this->sectionClasses[$section] = $type;
	}

	/**
	 * @param string $type 'admin' or 'personal'
	 * @return ISection[]
	 */
	protected function getSections(string $type): array {
		if (!isset($this->sections[$type])) {
			$this->sections[$type] = [];
		}

		foreach ($this->sectionClasses as $class => $sectionType) {
			try {
				/** @var ISection $section */
				$section = \OC::$server->query($class);
			} catch (QueryException $e) {
				$this->log->logException($e, ['level' => Util::INFO]);
				continue;
			}

			if (!$section instanceof ISection) {
				$this->log->logException(new \InvalidArgumentException('Invalid settings section registered'), ['level' => Util::INFO]);
				continue;
			}

			$this->sections[$sectionType][$section->getID()] = $section;

			unset($this->sectionClasses[$class]);
		}

		return $this->sections[$type];
	}

	/** @var array */
	protected $settingClasses = [];

	/** @var array */
	protected $settings = [];

	/**
	 * @param string $type 'admin' or 'personal'
	 * @param string $setting Class must implement OCP\Settings\ISetting
	 * @return void
	 */
	public function registerSetting(string $type, string $setting) {
		$this->settingClasses[$setting] = $type;
	}

	/**
	 * @param string $type 'admin' or 'personal'
	 * @param string $section
	 * @return ISettings[]
	 */
	protected function getSettings(string $type, string $section): array {
		if (!isset($this->settings[$type])) {
			$this->settings[$type] = [];
		}
		if (!isset($this->settings[$type][$section])) {
			$this->settings[$type][$section] = [];
		}

		foreach ($this->settingClasses as $class => $settingsType) {
			try {
				/** @var ISettings $setting */
				$setting = \OC::$server->query($class);
			} catch (QueryException $e) {
				$this->log->logException($e, ['level' => Util::INFO]);
				continue;
			}

			if (!$setting instanceof ISettings) {
				$this->log->logException(new \InvalidArgumentException('Invalid settings setting registered'), ['level' => Util::INFO]);
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
		$sections = [
			0 => [new Section('server', $this->l->t('Basic settings'), 0, $this->url->imagePath('settings', 'admin.svg'))],
			5 => [new Section('sharing', $this->l->t('Sharing'), 0, $this->url->imagePath('core', 'actions/share.svg'))],
			10 => [new Section('security', $this->l->t('Security'), 0, $this->url->imagePath('core', 'actions/password.svg'))],
			45 => [new Section('encryption', $this->l->t('Encryption'), 0, $this->url->imagePath('core', 'actions/password.svg'))],
			98 => [new Section('additional', $this->l->t('Additional settings'), 0, $this->url->imagePath('core', 'actions/settings-dark.svg'))],
			99 => [new Section('tips-tricks', $this->l->t('Tips & tricks'), 0, $this->url->imagePath('settings', 'help.svg'))],
		];

		$appSections = $this->getSections('admin');

		foreach ($appSections as $section) {
			/** @var ISection $section */
			if (!isset($sections[$section->getPriority()])) {
				$sections[$section->getPriority()] = [];
			}

			$sections[$section->getPriority()][] = $section;
		}

		ksort($sections);

		return $sections;
	}

	/**
	 * @param string $section
	 * @return ISection[]
	 */
	private function getBuiltInAdminSettings($section): array {
		$forms = [];

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

		return $forms;
	}

	/**
	 * @param string $section
	 * @return ISection[]
	 */
	private function getBuiltInPersonalSettings($section): array {
		$forms = [];

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
			$form = new Personal\Additional();
			$forms[$form->getPriority()] = [$form];
		}

		return $forms;
	}

	/**
	 * @inheritdoc
	 */
	public function getAdminSettings($section): array {
		$settings = $this->getBuiltInAdminSettings($section);
		$appSettings = $this->getSettings('admin', $section);

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
		$sections = [
			0 => [new Section('personal-info', $this->l->t('Personal info'), 0, $this->url->imagePath('core', 'actions/info.svg'))],
			5 => [new Section('security', $this->l->t('Security'), 0, $this->url->imagePath('settings', 'password.svg'))],
			15 => [new Section('sync-clients', $this->l->t('Sync clients'), 0, $this->url->imagePath('settings', 'change.svg'))],
		];

		$legacyForms = \OC_App::getForms('personal');
		if(!empty($legacyForms) && $this->hasLegacyPersonalSettingsToRender($legacyForms)) {
			$sections[98] = [new Section('additional', $this->l->t('Additional settings'), 0, $this->url->imagePath('core', 'actions/settings-dark.svg'))];
		}

		$appSections = $this->getSections('personal');

		foreach ($appSections as $section) {
			/** @var ISection $section */
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
	 * @return bool
	 */
	private function hasLegacyPersonalSettingsToRender(array $forms): bool {
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
	public function getPersonalSettings($section): array {
		$settings = $this->getBuiltInPersonalSettings($section);
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
}
