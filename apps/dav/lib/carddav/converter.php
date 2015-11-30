<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\CardDAV;

use OCP\IUser;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\Text;

class Converter {

	public function __construct() {
	}

	public function createCardFromUser(IUser $user) {

		$uid = $user->getUID();
		$displayName = $user->getDisplayName();
		$displayName = empty($displayName ) ? $uid : $displayName;
		$emailAddress = $user->getEMailAddress();
		$cloudId = $user->getCloudId();
		$image = $user->getAvatarImage(-1);

		$vCard = new VCard();
		$vCard->add(new Text($vCard, 'UID', $uid));
		if (!empty($displayName)) {
			$vCard->add(new Text($vCard, 'FN', $displayName));
		}
		if (!empty($emailAddress)) {
			$vCard->add(new Text($vCard, 'EMAIL', $emailAddress));
		}
		if (!empty($cloudId)) {
			$vCard->add(new Text($vCard, 'CLOUD', $cloudId));
		}
		if ($image) {
			$vCard->add('PHOTO', $image->data(), ['ENCODING' => 'b', 'TYPE' => $image->mimeType()]);
		}
		$vCard->validate();

		return $vCard;
	}

	public function updateCard(VCard $vCard, IUser $user) {
		$uid = $user->getUID();
		$displayName = $user->getDisplayName();
		$displayName = empty($displayName ) ? $uid : $displayName;
		$emailAddress = $user->getEMailAddress();
		$cloudId = $user->getCloudId();
		$image = $user->getAvatarImage(-1);

		$updated = false;
		if(!is_null($vCard->FN) && $vCard->FN->getValue() !== $displayName) {
			$vCard->FN = new Text($vCard, 'FN', $displayName);
			$updated = true;
		}
		if(!is_null($vCard->EMail) && $vCard->EMail->getValue() !== $emailAddress) {
			$vCard->EMAIL = new Text($vCard, 'EMAIL', $emailAddress);
			$updated = true;
		}
		if(!is_null($vCard->CLOUD) && $vCard->CLOUD->getValue() !== $cloudId) {
			$vCard->CLOUD = new Text($vCard, 'CLOUD', $cloudId);
			$updated = true;
		}

		if (empty($emailAddress) && !is_null($vCard->EMAIL)) {
			unset($vCard->EMAIL);
			$updated = true;
		}
		if (empty($cloudId) && !is_null($vCard->CLOUD)) {
			unset($vCard->CLOUD);
			$updated = true;
		}
		if (empty($image) && !is_null($vCard->PHOTO)) {
			unset($vCard->PHOTO);
			$updated = true;
		}

		return $updated;
	}
}
