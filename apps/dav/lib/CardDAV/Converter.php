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
	private const array NAME_SUFFIXES = [
		'i', 'ii', 'iii', 'iv', 'v',
		'senior', 'junior', 'jr', 'sr',
		'phd', 'apr', 'rph', 'pe', 'md', 'ma', 'msc', 'bsc', 'ba', 'bs',
		'dmd', 'cme', 'bsn', 'mba',
		'ceo', 'cto', 'cfo', 'coo',
	];
	private const array NAME_SALUTATIONS = [
		'mr', 'mrs', 'ms', 'miss', 'master', 'mister', 'dr', 'rev', 'fr', 'prof',
		'herr', 'frau', 'mme', 'mlle', 'me', 'pr',
	];

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
		// Based on https://github.com/joshfraser/PHP-Name-Parser

		$prefix = [];
		$suffix = [];
		$cleanedName = preg_replace('/\([^()]*\)|\[[^[\]]*\]|\{[^{}]*\}/', ' ', $fullName) ?? $fullName;
		$cleanedName = trim(preg_replace('/\s+/', ' ', $cleanedName) ?? $cleanedName);
		if ($cleanedName === '') {
			$cleanedName = trim($fullName);
		}

		$segments = array_values(array_filter(
			array_map($this->splitNameWords(...), explode(',', $cleanedName)),
			static fn (array $segment): bool => $segment !== [],
		));

		while (count($segments) > 1) {
			$lastSegment = $segments[count($segments) - 1];
			$knownSuffix = array_filter(
				$lastSegment,
				fn (string $word): bool => !$this->isNameSuffix($word),
			) === [];
			if (!$knownSuffix) {
				break;
			}
			array_unshift($suffix, implode(' ', array_pop($segments)));
		}

		if (count($segments) > 1 && count($segments[0]) === 1) {
			$result = [$segments[0][0], '', '', '', ''];
			$nameWords = array_merge(...array_slice($segments, 1));
		} else {
			$result = ['', '', '', '', ''];
			$nameWords = $segments[0] ?? $this->splitNameWords($cleanedName);
			if (count($segments) > 1) {
				$suffix[] = implode(', ', array_map(static fn (array $segment): string => implode(' ', $segment), array_slice($segments, 1)));
			}
		}

		while (count($nameWords) > 1 && $this->isNameSalutation($nameWords[0])) {
			$prefix[] = array_shift($nameWords);
		}
		while (count($nameWords) > 1 && $this->isNameSuffix($nameWords[count($nameWords) - 1])) {
			array_unshift($suffix, array_pop($nameWords));
		}

		if ($result[0] !== '') {
			$result[1] = $nameWords[0] ?? '';
			$result[2] = implode(' ', array_slice($nameWords, 1));
		} elseif (count($nameWords) > 2) {
			$result[0] = implode(' ', array_slice($nameWords, count($nameWords) - 1));
			$result[1] = $nameWords[0];
			$result[2] = implode(' ', array_slice($nameWords, 1, count($nameWords) - 2));
		} elseif (count($nameWords) === 2) {
			$result[0] = $nameWords[1];
			$result[1] = $nameWords[0];
		} elseif (count($nameWords) === 1) {
			$result[0] = $nameWords[0];
		}

		$result[3] = implode(' ', $prefix);
		$result[4] = implode(', ', array_filter($suffix));

		return $result;
	}

	private function splitNameWords(string $name): array {
		return preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
	}

	private function isNameSuffix(string $word): bool {
		return in_array($this->normalizeNameWord($word), self::NAME_SUFFIXES, true);
	}

	private function isNameSalutation(string $word): bool {
		return in_array($this->normalizeNameWord($word), self::NAME_SALUTATIONS, true);
	}

	private function normalizeNameWord(string $word): string {
		return strtolower(preg_replace('/[.,]/', '', trim($word)) ?? $word);
	}

	private function getAvatarImage(IUser $user): ?IImage {
		try {
			return $user->getAvatarImage(512);
		} catch (Exception $ex) {
			return null;
		}
	}
}
