<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Settings\Personal;

use OC\Profile\ProfileManager;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Provisioning_API\Controller\AUserDataOCSController;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\FileInfo;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Server;
use OCP\Settings\ISettings;

class PersonalInfo implements ISettings {

	/** @var ProfileManager */
	private $profileManager;

	public function __construct(
		private IConfig $config,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IAccountManager $accountManager,
		ProfileManager $profileManager,
		private IAppManager $appManager,
		private IFactory $l10nFactory,
		private IL10N $l,
		private IInitialState $initialStateService,
		private IManager $manager,
	) {
		$this->profileManager = $profileManager;
	}

	public function getForm(): TemplateResponse {
		$federationEnabled = $this->appManager->isEnabledForUser('federation');
		$federatedFileSharingEnabled = $this->appManager->isEnabledForUser('federatedfilesharing');
		$lookupServerUploadEnabled = false;
		if ($federatedFileSharingEnabled) {
			/** @var FederatedShareProvider $shareProvider */
			$shareProvider = Server::get(FederatedShareProvider::class);
			$lookupServerUploadEnabled = $shareProvider->isLookupServerUploadEnabled();
		}

		$uid = \OC_User::getUser();
		$user = $this->userManager->get($uid);
		$account = $this->accountManager->getAccount($user);

		// make sure FS is setup before querying storage related stuff...
		\OC_Util::setupFS($user->getUID());

		$storageInfo = \OC_Helper::getStorageInfo('/');
		if ($storageInfo['quota'] === FileInfo::SPACE_UNLIMITED) {
			$totalSpace = $this->l->t('Unlimited');
		} else {
			$totalSpace = \OC_Helper::humanFileSize($storageInfo['total']);
		}

		$messageParameters = $this->getMessageParameters($account);

		$parameters = [
			'lookupServerUploadEnabled' => $lookupServerUploadEnabled,
			'isFairUseOfFreePushService' => $this->isFairUseOfFreePushService(),
			'profileEnabledGlobally' => $this->profileManager->isProfileEnabled(),
		] + $messageParameters;

		$personalInfoParameters = [
			'userId' => $uid,
			'avatar' => $this->getProperty($account, IAccountManager::PROPERTY_AVATAR),
			'groups' => $this->getGroups($user),
			'quota' => $storageInfo['quota'],
			'totalSpace' => $totalSpace,
			'usage' => \OC_Helper::humanFileSize($storageInfo['used']),
			'usageRelative' => round($storageInfo['relative']),
			'displayName' => $this->getProperty($account, IAccountManager::PROPERTY_DISPLAYNAME),
			'emailMap' => $this->getEmailMap($account),
			'phone' => $this->getProperty($account, IAccountManager::PROPERTY_PHONE),
			'defaultPhoneRegion' => $this->config->getSystemValueString('default_phone_region'),
			'location' => $this->getProperty($account, IAccountManager::PROPERTY_ADDRESS),
			'website' => $this->getProperty($account, IAccountManager::PROPERTY_WEBSITE),
			'twitter' => $this->getProperty($account, IAccountManager::PROPERTY_TWITTER),
			'fediverse' => $this->getProperty($account, IAccountManager::PROPERTY_FEDIVERSE),
			'languageMap' => $this->getLanguageMap($user),
			'localeMap' => $this->getLocaleMap($user),
			'profileEnabledGlobally' => $this->profileManager->isProfileEnabled(),
			'profileEnabled' => $this->profileManager->isProfileEnabled($user),
			'organisation' => $this->getProperty($account, IAccountManager::PROPERTY_ORGANISATION),
			'role' => $this->getProperty($account, IAccountManager::PROPERTY_ROLE),
			'headline' => $this->getProperty($account, IAccountManager::PROPERTY_HEADLINE),
			'biography' => $this->getProperty($account, IAccountManager::PROPERTY_BIOGRAPHY),
			'birthdate' => $this->getProperty($account, IAccountManager::PROPERTY_BIRTHDATE),
			'firstDayOfWeek' => $this->config->getUserValue($uid, 'core', AUserDataOCSController::USER_FIELD_FIRST_DAY_OF_WEEK),
			'pronouns' => $this->getProperty($account, IAccountManager::PROPERTY_PRONOUNS),
		];

		$accountParameters = [
			'avatarChangeSupported' => $user->canChangeAvatar(),
			'displayNameChangeSupported' => $user->canChangeDisplayName(),
			'emailChangeSupported' => $user->canChangeEmail(),
			'federationEnabled' => $federationEnabled,
			'lookupServerUploadEnabled' => $lookupServerUploadEnabled,
		];

		$profileParameters = [
			'profileConfig' => $this->profileManager->getProfileConfigWithMetadata($user, $user),
		];

		$this->initialStateService->provideInitialState('profileEnabledGlobally', $this->profileManager->isProfileEnabled());
		$this->initialStateService->provideInitialState('personalInfoParameters', $personalInfoParameters);
		$this->initialStateService->provideInitialState('accountParameters', $accountParameters);
		$this->initialStateService->provideInitialState('profileParameters', $profileParameters);

		return new TemplateResponse('settings', 'settings/personal/personal.info', $parameters, '');
	}

	/**
	 * Check if is fair use of free push service
	 * @return boolean
	 */
	private function isFairUseOfFreePushService(): bool {
		return $this->manager->isFairUseOfFreePushService();
	}

	/**
	 * returns the property data in an
	 * associative array
	 */
	private function getProperty(IAccount $account, string $property): array {
		$property = [
			'name' => $account->getProperty($property)->getName(),
			'value' => $account->getProperty($property)->getValue(),
			'scope' => $account->getProperty($property)->getScope(),
			'verified' => $account->getProperty($property)->getVerified(),
		];

		return $property;
	}

	/**
	 * returns the section ID string, e.g. 'sharing'
	 * @since 9.1
	 */
	public function getSection(): string {
		return 'personal-info';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority(): int {
		return 10;
	}

	/**
	 * returns a sorted list of the user's group GIDs
	 */
	private function getGroups(IUser $user): array {
		$groups = array_map(
			static function (IGroup $group) {
				return $group->getDisplayName();
			},
			$this->groupManager->getUserGroups($user)
		);
		sort($groups);

		return $groups;
	}

	/**
	 * returns the primary email and additional emails in an
	 * associative array
	 */
	private function getEmailMap(IAccount $account): array {
		$systemEmail = [
			'name' => $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getName(),
			'value' => $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue(),
			'scope' => $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getScope(),
			'verified' => $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getVerified(),
		];

		$additionalEmails = array_map(
			function (IAccountProperty $property) {
				return [
					'name' => $property->getName(),
					'value' => $property->getValue(),
					'scope' => $property->getScope(),
					'verified' => $property->getVerified(),
					'locallyVerified' => $property->getLocallyVerified(),
				];
			},
			$account->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)->getProperties(),
		);

		$emailMap = [
			'primaryEmail' => $systemEmail,
			'additionalEmails' => $additionalEmails,
			'notificationEmail' => (string)$account->getUser()->getPrimaryEMailAddress(),
		];

		return $emailMap;
	}

	/**
	 * returns the user's active language, common languages, and other languages in an
	 * associative array
	 */
	private function getLanguageMap(IUser $user): array {
		$forceLanguage = $this->config->getSystemValue('force_language', false);
		if ($forceLanguage !== false) {
			return [];
		}

		$uid = $user->getUID();

		$userConfLang = $this->config->getUserValue($uid, 'core', 'lang', $this->l10nFactory->findLanguage());
		$languages = $this->l10nFactory->getLanguages();

		// associate the user language with the proper array
		$userLangIndex = array_search($userConfLang, array_column($languages['commonLanguages'], 'code'));
		$userLang = $languages['commonLanguages'][$userLangIndex];
		// search in the other languages
		if ($userLangIndex === false) {
			$userLangIndex = array_search($userConfLang, array_column($languages['otherLanguages'], 'code'));
			$userLang = $languages['otherLanguages'][$userLangIndex];
		}
		// if user language is not available but set somehow: show the actual code as name
		if (!is_array($userLang)) {
			$userLang = [
				'code' => $userConfLang,
				'name' => $userConfLang,
			];
		}

		return array_merge(
			['activeLanguage' => $userLang],
			$languages
		);
	}

	private function getLocaleMap(IUser $user): array {
		$forceLanguage = $this->config->getSystemValue('force_locale', false);
		if ($forceLanguage !== false) {
			return [];
		}

		$uid = $user->getUID();
		$userLang = $this->config->getUserValue($uid, 'core', 'lang', $this->l10nFactory->findLanguage());
		$userLocaleString = $this->config->getUserValue($uid, 'core', 'locale', $this->l10nFactory->findLocale($userLang));
		$localeCodes = $this->l10nFactory->findAvailableLocales();
		$userLocale = array_filter($localeCodes, fn ($value) => $userLocaleString === $value['code']);

		if (!empty($userLocale)) {
			$userLocale = reset($userLocale);
		}

		$localesForLanguage = array_values(array_filter($localeCodes, fn ($localeCode) => str_starts_with($localeCode['code'], $userLang)));
		$otherLocales = array_values(array_filter($localeCodes, fn ($localeCode) => !str_starts_with($localeCode['code'], $userLang)));

		if (!$userLocale) {
			$userLocale = [
				'code' => 'en',
				'name' => 'English'
			];
		}

		return [
			'activeLocaleLang' => $userLocaleString,
			'activeLocale' => $userLocale,
			'localesForLanguage' => $localesForLanguage,
			'otherLocales' => $otherLocales,
		];
	}

	/**
	 * returns the message parameters
	 */
	private function getMessageParameters(IAccount $account): array {
		$needVerifyMessage = [IAccountManager::PROPERTY_EMAIL, IAccountManager::PROPERTY_WEBSITE, IAccountManager::PROPERTY_TWITTER];
		$messageParameters = [];
		foreach ($needVerifyMessage as $property) {
			switch ($account->getProperty($property)->getVerified()) {
				case IAccountManager::VERIFIED:
					$message = $this->l->t('Verifying');
					break;
				case IAccountManager::VERIFICATION_IN_PROGRESS:
					$message = $this->l->t('Verifying …');
					break;
				default:
					$message = $this->l->t('Verify');
			}
			$messageParameters[$property . 'Message'] = $message;
		}
		return $messageParameters;
	}
}
