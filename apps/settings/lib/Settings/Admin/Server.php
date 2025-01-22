<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OC\Profile\ProfileManager;
use OC\Profile\TProfileHelper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;

class Server implements IDelegatedSettings {
	use TProfileHelper;

	public function __construct(
		private IDBConnection $connection,
		private IInitialState $initialStateService,
		private ProfileManager $profileManager,
		private ITimeFactory $timeFactory,
		private IURLGenerator $urlGenerator,
		private IConfig $config,
		private IAppConfig $appConfig,
		private IL10N $l,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$ownerConfigFile = fileowner(\OC::$configDir . 'config.php');
		$cliBasedCronPossible = function_exists('posix_getpwuid') && $ownerConfigFile !== false;
		$cliBasedCronUser = $cliBasedCronPossible ? (posix_getpwuid($ownerConfigFile)['name'] ?? '') : '';

		// Background jobs
		$this->initialStateService->provideInitialState('backgroundJobsMode', $this->appConfig->getValueString('core', 'backgroundjobs_mode', 'ajax'));
		$this->initialStateService->provideInitialState('lastCron', $this->appConfig->getValueInt('core', 'lastcron', 0));
		$this->initialStateService->provideInitialState('cronMaxAge', $this->cronMaxAge());
		$this->initialStateService->provideInitialState('cronErrors', $this->config->getAppValue('core', 'cronErrors'));
		$this->initialStateService->provideInitialState('cliBasedCronPossible', $cliBasedCronPossible);
		$this->initialStateService->provideInitialState('cliBasedCronUser', $cliBasedCronUser);
		$this->initialStateService->provideInitialState('backgroundJobsDocUrl', $this->urlGenerator->linkToDocs('admin-background-jobs'));

		// Profile page
		$this->initialStateService->provideInitialState('profileEnabledGlobally', $this->profileManager->isProfileEnabled());
		$this->initialStateService->provideInitialState('profileEnabledByDefault', $this->isProfileEnabledByDefault($this->config));

		// Basic settings
		$this->initialStateService->provideInitialState('restrictSystemTagsCreationToAdmin', $this->appConfig->getValueString('systemtags', 'restrict_creation_to_admin', 'true'));

		return new TemplateResponse('settings', 'settings/admin/server', [
			'profileEnabledGlobally' => $this->profileManager->isProfileEnabled(),
		], '');
	}

	protected function cronMaxAge(): int {
		$query = $this->connection->getQueryBuilder();
		$query->select('last_checked')
			->from('jobs')
			->orderBy('last_checked', 'ASC')
			->setMaxResults(1);

		$result = $query->execute();
		if ($row = $result->fetch()) {
			$maxAge = (int)$row['last_checked'];
		} else {
			$maxAge = $this->timeFactory->getTime();
		}
		$result->closeCursor();

		return $maxAge;
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'server';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 0;
	}

	public function getName(): ?string {
		return $this->l->t('Background jobs');
	}

	public function getAuthorizedAppConfig(): array {
		return [
			'core' => [
				'/mail_general_settings/',
			],
		];
	}
}
