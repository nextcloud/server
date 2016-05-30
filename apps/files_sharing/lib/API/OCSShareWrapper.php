<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace OCA\Files_Sharing\API;

class OCSShareWrapper {

	/**
	 * @return Share20OCS
	 */
	private function getShare20OCS() {
		return new Share20OCS(
			\OC::$server->getShareManager(),
			\OC::$server->getGroupManager(),
			\OC::$server->getUserManager(),
			\OC::$server->getRequest(),
			\OC::$server->getRootFolder(),
			\OC::$server->getURLGenerator(),
			\OC::$server->getUserSession()->getUser(),
			\OC::$server->getL10N('files_sharing')
		);
	}

	public function getAllShares() {
		return $this->getShare20OCS()->getShares();
	}

	public function createShare() {
		return $this->getShare20OCS()->createShare();
	}

	public function getShare($params) {
		$id = $params['id'];
		return $this->getShare20OCS()->getShare($id);
	}

	public function updateShare($params) {
		$id = $params['id'];
		return $this->getShare20OCS()->updateShare($id);
	}

	public function deleteShare($params) {
		$id = $params['id'];
		return $this->getShare20OCS()->deleteShare($id);
	}
}
