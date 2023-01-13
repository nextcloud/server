<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CardDAV;

use Exception;
use OCP\Accounts\IAccountManager;
use OCP\IImage;
use OCP\IUser;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\Text;

class Converter {

	/** @var IAccountManager */
	private $accountManager;

	public function __construct(IAccountManager $accountManager) {
		$this->accountManager = $accountManager;
	}

	public function createCardFromUser(IUser $user): ?VCard {
		$userProperties = $this->accountManager->getAccount($user)->getProperties();

		$uid = $user->getUID();
		$cloudId = $user->getCloudId();
		$image = $this->getAvatarImage($user);

		$vCard = new VCard();
		$vCard->VERSION = '3.0';
		$vCard->UID = $uid;

		$publish = false;

		foreach ($userProperties as $property) {
			$shareWithTrustedServers =
				$property->getScope() === IAccountManager::SCOPE_FEDERATED ||
				$property->getScope() === IAccountManager::SCOPE_PUBLISHED;

			$emptyValue = $property->getValue() === '';

			if ($shareWithTrustedServers && !$emptyValue) {
				$publish = true;
				switch ($property->getName()) {
					case IAccountManager::PROPERTY_DISPLAYNAME:
						$vCard->add(new Text($vCard, 'FN', $property->getValue()));
						$vCard->add(new Text($vCard, 'N', $this->splitFullName($property->getValue())));
						break;
					case IAccountManager::PROPERTY_AVATAR:
						if ($image !== null) {
							$vCard->add('PHOTO', $image->data(), ['ENCODING' => 'b', 'TYPE' => $image->mimeType()]);
						}
						break;
					case IAccountManager::PROPERTY_EMAIL:
						$vCard->add(new Text($vCard, 'EMAIL', $property->getValue(), ['TYPE' => 'OTHER']));
						break;
					case IAccountManager::PROPERTY_WEBSITE:
						$vCard->add(new Text($vCard, 'URL', $property->getValue()));
						break;
					case IAccountManager::PROPERTY_PHONE:
						$vCard->add(new Text($vCard, 'TEL', $property->getValue(), ['TYPE' => 'OTHER']));
						break;
					case IAccountManager::PROPERTY_ADDRESS:
						$vCard->add(new Text($vCard, 'ADR', $property->getValue(), ['TYPE' => 'OTHER']));
						break;
					case IAccountManager::PROPERTY_TWITTER:
						$vCard->add(new Text($vCard, 'X-SOCIALPROFILE', $property->getValue(), ['TYPE' => 'TWITTER']));
						break;
				}
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
			return $user->getAvatarImage(-1);
		} catch (Exception $ex) {
			return null;
		}
	}
}
