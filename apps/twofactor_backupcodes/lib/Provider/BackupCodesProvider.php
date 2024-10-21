<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Provider;

use OC\App\AppManager;
use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCA\TwoFactorBackupCodes\Settings\Personal;
use OCP\Authentication\TwoFactorAuth\IDeactivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Template;

class BackupCodesProvider implements IDeactivatableByAdmin, IProvidesPersonalSettings {

	/** @var AppManager */
	private $appManager;

	/**
	 * @param string $appName
	 * @param BackupCodeStorage $storage
	 * @param IL10N $l10n
	 * @param AppManager $appManager
	 */
	public function __construct(
		private string $appName,
		private BackupCodeStorage $storage,
		private IL10N $l10n,
		AppManager $appManager,
		private IInitialStateService $initialStateService,
	) {
		$this->appManager = $appManager;
	}

	/**
	 * Get unique identifier of this 2FA provider
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'backup_codes';
	}

	/**
	 * Get the display name for selecting the 2FA provider
	 *
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Backup code');
	}

	/**
	 * Get the description for selecting the 2FA provider
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return $this->l10n->t('Use backup code');
	}

	/**
	 * Get the template for rending the 2FA provider view
	 *
	 * @param IUser $user
	 * @return Template
	 */
	public function getTemplate(IUser $user): Template {
		return new Template('twofactor_backupcodes', 'challenge');
	}

	/**
	 * Verify the given challenge
	 *
	 * @param IUser $user
	 * @param string $challenge
	 * @return bool
	 */
	public function verifyChallenge(IUser $user, string $challenge): bool {
		return $this->storage->validateCode($user, $challenge);
	}

	/**
	 * Decides whether 2FA is enabled for the given user
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isTwoFactorAuthEnabledForUser(IUser $user): bool {
		return $this->storage->hasBackupCodes($user);
	}

	/**
	 * Determine whether backup codes should be active or not
	 *
	 * Backup codes only make sense if at least one 2FA provider is active,
	 * hence this method checks all enabled apps on whether they provide 2FA
	 * functionality or not. If there's at least one app, backup codes are
	 * enabled on the personal settings page.
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isActive(IUser $user): bool {
		$appIds = array_filter($this->appManager->getEnabledAppsForUser($user), function ($appId) {
			return $appId !== $this->appName;
		});
		foreach ($appIds as $appId) {
			$info = $this->appManager->getAppInfo($appId);
			if (isset($info['two-factor-providers']) && count($info['two-factor-providers']) > 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param IUser $user
	 *
	 * @return IPersonalProviderSettings
	 */
	public function getPersonalSettings(IUser $user): IPersonalProviderSettings {
		$state = $this->storage->getBackupCodesState($user);
		$this->initialStateService->provideInitialState($this->appName, 'state', $state);
		return new Personal();
	}

	public function disableFor(IUser $user) {
		$this->storage->deleteCodes($user);
	}
}
