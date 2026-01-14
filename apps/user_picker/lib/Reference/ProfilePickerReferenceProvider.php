<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserPicker\Reference;

use OCA\UserPicker\AppInfo\Application;
use OCP\Accounts\IAccountManager;

use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Profile\IProfileManager;

class ProfilePickerReferenceProvider extends ADiscoverableReferenceProvider {
	public const RICH_OBJECT_TYPE = 'user_picker_profile';

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private IAccountManager $accountManager,
		private IProfileManager $profileManager,
		private ?string $userId,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'profile_picker';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Profile picker');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		return $this->getObjectId($referenceText) !== null;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if (!$this->matchReference($referenceText)) {
			return null;
		}

		$userId = $this->getObjectId($referenceText);
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return null;
		}
		if (!$this->profileManager->isProfileEnabled($user)) {
			return null;
		}
		$account = $this->accountManager->getAccount($user);

		$currentUser = $this->userManager->get($this->userId);

		$reference = new Reference($referenceText);

		$userDisplayName = $user->getDisplayName();
		$userEmail = $user->getEMailAddress();
		$userAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', ['userId' => $userId, 'size' => '64']);

		$bioProperty = $account->getProperty(IAccountManager::PROPERTY_BIOGRAPHY);
		$bio = null;
		$fullBio = null;
		if ($this->profileManager->isProfileFieldVisible(IAccountManager::PROPERTY_BIOGRAPHY, $user, $currentUser)) {
			$fullBio = $bioProperty->getValue();
			$bio = $fullBio !== ''
				? (mb_strlen($fullBio) > 80
					? (mb_substr($fullBio, 0, 80) . '...')
					: $fullBio)
				: null;
		}
		$headline = $account->getProperty(IAccountManager::PROPERTY_HEADLINE);
		$location = $account->getProperty(IAccountManager::PROPERTY_ADDRESS);
		$website = $account->getProperty(IAccountManager::PROPERTY_WEBSITE);
		$organisation = $account->getProperty(IAccountManager::PROPERTY_ORGANISATION);
		$role = $account->getProperty(IAccountManager::PROPERTY_ROLE);

		// for clients who can't render the reference widgets
		$reference->setTitle($userDisplayName);
		$reference->setDescription($userEmail ?? $userDisplayName);
		$reference->setImageUrl($userAvatarUrl);

		$isLocationVisible = $this->profileManager->isProfileFieldVisible(IAccountManager::PROPERTY_ADDRESS, $user, $currentUser);

		// for the Vue reference widget
		$reference->setRichObject(
			self::RICH_OBJECT_TYPE,
			[
				'user_id' => $userId,
				'title' => $userDisplayName,
				'subline' => $userEmail ?? $userDisplayName,
				'email' => $userEmail,
				'bio' => $bio,
				'full_bio' => $fullBio,
				'headline' => $this->profileManager->isProfileFieldVisible(IAccountManager::PROPERTY_HEADLINE, $user, $currentUser) ? $headline->getValue() : null,
				'location' => $isLocationVisible ? $location->getValue() : null,
				'location_url' => $isLocationVisible ? $this->getOpenStreetLocationUrl($location->getValue()) : null,
				'website' => $this->profileManager->isProfileFieldVisible(IAccountManager::PROPERTY_WEBSITE, $user, $currentUser) ? $website->getValue() : null,
				'organisation' => $this->profileManager->isProfileFieldVisible(IAccountManager::PROPERTY_ORGANISATION, $user, $currentUser) ? $organisation->getValue() : null,
				'role' => $this->profileManager->isProfileFieldVisible(IAccountManager::PROPERTY_ROLE, $user, $currentUser) ? $role->getValue() : null,
				'url' => $referenceText,
			]
		);
		return $reference;
	}

	public function getObjectId(string $url): ?string {
		$baseUrl = $this->urlGenerator->getBaseUrl();
		$baseWithIndex = $baseUrl . '/index.php';

		preg_match('/^' . preg_quote($baseUrl, '/') . '\/u\/(\w+)$/', $url, $matches);
		if (count($matches) > 1) {
			return $matches[1];
		}
		preg_match('/^' . preg_quote($baseWithIndex, '/') . '\/u\/(\w+)$/', $url, $matches);
		if (count($matches) > 1) {
			return $matches[1];
		}

		return null;
	}

	public function getOpenStreetLocationUrl($location): string {
		return 'https://www.openstreetmap.org/search?query=' . urlencode($location);
	}

	/**
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {
		$objectId = $this->getObjectId($referenceId);
		if ($objectId !== null) {
			return $objectId;
		}
		return $referenceId;
	}
}
