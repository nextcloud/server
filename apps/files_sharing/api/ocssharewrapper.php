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
		$manager =new \OC\Share20\Manager(
			\OC::$server->getLogger(),
			\OC::$server->getConfig(),
			\OC::$server->getSecureRandom(),
			\OC::$server->getHasher(),
			\OC::$server->getMountManager(),
			\OC::$server->getGroupManager(),
			\OC::$server->getL10N('core')
		);

		$manager->registerProvider('ocdefault',
			[
				\OCP\Share::SHARE_TYPE_USER,
				\OCP\SHARE::SHARE_TYPE_GROUP,
				\OCP\SHARE::SHARE_TYPE_LINK
			],
			function() {
				return new \OC\Share20\DefaultShareProvider(
					\OC::$server->getDatabaseConnection(),
					\OC::$server->getUserManager(),
					\OC::$server->getGroupManager(),
					\OC::$server->getRootFolder()
				);
			}
			);

		return new Share20OCS(
			$manager,
			\OC::$server->getGroupManager(),
			\OC::$server->getUserManager(),
			\OC::$server->getRequest(),
			\OC::$server->getRootFolder(),
			\OC::$server->getURLGenerator(),
			\OC::$server->getUserSession()->getUser());
	}

	public function getAllShares($params) {
		return \OCA\Files_Sharing\API\Local::getAllShares($params);
	}

	public function createShare() {
		return $this->getShare20OCS()->createShare();
	}

	public function getShare($params) {
		$id = $params['id'];
		return $this->getShare20OCS()->getShare($id);
	}

	public function updateShare($params) {
		return \OCA\Files_Sharing\API\Local::updateShare($params);
	}

	public function deleteShare($params) {
		$id = $params['id'];
		return $this->getShare20OCS()->deleteShare($id);
	}
}
