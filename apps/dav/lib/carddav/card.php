<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

class Card extends \Sabre\CardDAV\Card {

	function getACL() {
		$acl = parent::getACL();
		if ($this->getOwner() === 'principals/system/system') {
			$acl[] = [
				'privilege' => '{DAV:}read',
				'principal' => '{DAV:}authenticated',
				'protected' => true,
			];
		}

		/** @var CardDavBackend $carddavBackend */
		$carddavBackend = $this->carddavBackend;
		return $carddavBackend->applyShareAcl($this->getBookId(), $acl);
	}

	private function getBookId() {
		return $this->addressBookInfo['id'];
	}

}
