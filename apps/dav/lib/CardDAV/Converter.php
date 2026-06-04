<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use DateTimeImmutable;
use Exception;
use OCP\Accounts\IAccountManager;
use OCP\IImage;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\Text;
use Sabre\VObject\Property\VCard\Date;

class Converter {
	public function __construct(
		private IAccountManager $accountManager,
		private IUserManager $userManager,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	public function createCardFromUser(IUser $user): ?VCard {
		$userProperties = $this->accountManager->getAccount($user)->getAllProperties();

		$uid = $user->getUID();
		$cloudId = $user->getCloudId();
		$image = $this->getAvatarImage($user);

		$vCard = new VCard();
		$vCard->VERSION = '3.0';
		$vCard->UID = $uid;

		$publish = false;

		foreach ($userProperties as $property) {
			if ($property->getName() !== IAccountManager::PROPERTY_AVATAR && empty($property->getValue())) {
				continue;
			}

			$scope = $property->getScope();
			// Do not write private data to the system address book at all
			if ($scope === IAccountManager::SCOPE_PRIVATE || empty($scope)) {
				continue;
			}

			$publish = true;
			switch ($property->getName()) {
				case IAccountManager::PROPERTY_DISPLAYNAME:
					$vCard->add(new Text($vCard, 'FN', $property->getValue(), ['X-NC-SCOPE' => $scope]));
					$vCard->add(new Text($vCard, 'N', $this->splitFullName($property->getValue()), ['X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_AVATAR:
					if ($image !== null) {
						$vCard->add('PHOTO', $image->data(), ['ENCODING' => 'b', 'TYPE' => $image->mimeType(), ['X-NC-SCOPE' => $scope]]);
					}
					break;
				case IAccountManager::COLLECTION_EMAIL:
				case IAccountManager::PROPERTY_EMAIL:
					$vCard->add(new Text($vCard, 'EMAIL', $property->getValue(), ['TYPE' => 'OTHER', 'X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_WEBSITE:
					$vCard->add(new Text($vCard, 'URL', $property->getValue(), ['X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_PROFILE_ENABLED:
					if ($property->getValue()) {
						$vCard->add(
							new Text(
								$vCard,
								'X-SOCIALPROFILE',
								$this->urlGenerator->linkToRouteAbsolute('profile.ProfilePage.index', ['targetUserId' => $user->getUID()]),
								[
									'TYPE' => 'NEXTCLOUD',
									'X-NC-SCOPE' => IAccountManager::SCOPE_PUBLISHED
								]
							)
						);
					}
					break;
				case IAccountManager::PROPERTY_PHONE:
					$vCard->add(new Text($vCard, 'TEL', $property->getValue(), ['TYPE' => 'VOICE', 'X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_ADDRESS:
					// structured prop: https://www.rfc-editor.org/rfc/rfc6350.html#section-6.3.1
					// post office box;extended address;street address;locality;region;postal code;country
					$vCard->add(
						new Text(
							$vCard,
							'ADR',
							[ '', '', '', $property->getValue(), '', '', ''	],
							[
								'TYPE' => 'OTHER',
								'X-NC-SCOPE' => $scope,
							]
						)
					);
					break;
				case IAccountManager::PROPERTY_TWITTER:
					$vCard->add(new Text($vCard, 'X-SOCIALPROFILE', $property->getValue(), ['TYPE' => 'TWITTER', 'X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_ORGANISATION:
					$vCard->add(new Text($vCard, 'ORG', $property->getValue(), ['X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_ROLE:
					$vCard->add(new Text($vCard, 'TITLE', $property->getValue(), ['X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_BIOGRAPHY:
					$vCard->add(new Text($vCard, 'NOTE', $property->getValue(), ['X-NC-SCOPE' => $scope]));
					break;
				case IAccountManager::PROPERTY_BIRTHDATE:
					try {
						$birthdate = new DateTimeImmutable($property->getValue());
					} catch (Exception $e) {
						// Invalid date -> just skip the property
						$this->logger->info("Failed to parse user's birthdate for the SAB: " . $property->getValue(), [
							'exception' => $e,
							'userId' => $user->getUID(),
						]);
						break;
					}
					$dateProperty = new Date($vCard, 'BDAY', null, ['X-NC-SCOPE' => $scope]);
					$dateProperty->setDateTime($birthdate);
					$vCard->add($dateProperty);
					break;
			}
		}

		// Local properties
		$managers = $user->getManagerUids();
		// X-MANAGERSNAME only allows a single value, so we take the first manager
		if (isset($managers[0])) {
			$displayName = $this->userManager->getDisplayName($managers[0]);
			// Only set the manager if a user object is found
			if ($displayName !== null) {
				$vCard->add(new Text($vCard, 'X-MANAGERSNAME', $displayName, [
					'uid' => $managers[0],
					'X-NC-SCOPE' => IAccountManager::SCOPE_LOCAL,
				]));
			}
		}

		if ($publish && !empty($cloudId)) {
			$vCard->add(new Text($vCard, 'CLOUD', $cloudId));
			$vCard->validate();
			return $vCard;
		}

		return null;
	}

	public function splitFullName(string $fullName): array {
		// Very basic western style parsing. I'm not gonna implement
		// https://github.com/android/platform_packages_providers_contactsprovider/blob/master/src/com/android/providers/contacts/NameSplitter.java ;)

		$elements = explode(' ', $fullName);
		$result = ['', '', '', '', ''];
		if (count($elements) > 2) {
			$result[0] = implode(' ', array_slice($elements, count($elements) - 1));
			$result[1] = $elements[0];
			$result[2] = implode(' ', array_slice($elements, 1, count($elements) - 2));
		} elseif (count($elements) === 2) {
			$result[0] = $elements[1];
			$result[1] = $elements[0];
		} else {
			$result[0] = $elements[0];
		}

		return $result;
	}

	private function getAvatarImage(IUser $user): ?IImage {
		try {
			return $user->getAvatarImage(512);
		} catch (Exception $ex) {
			return null;
		}
	}
}
