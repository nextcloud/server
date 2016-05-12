<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OC\OCS;

class Cloud {

	public static function getCapabilities() {
		$result = array();
		list($major, $minor, $micro) = \OCP\Util::getVersion();
		$result['version'] = array(
			'major' => $major,
			'minor' => $minor,
			'micro' => $micro,
			'string' => \OC_Util::getVersionString(),
			'edition' => \OC_Util::getEditionString(),
			);
			
		$result['capabilities'] = \OC::$server->getCapabilitiesManager()->getCapabilities();

		return new Result($result);
	}
	
	public static function getCurrentUser() {
		$userObject = \OC::$server->getUserManager()->get(\OC_User::getUser());
		$data  = array(
			'id' => $userObject->getUID(),
			'display-name' => $userObject->getDisplayName(),
			'email' => $userObject->getEMailAddress(),
		);
		return new Result($data);
	}
}
