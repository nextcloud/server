<?php
/**
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Controller;

use OC\CapabilitiesManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class OCSController extends \OCP\AppFramework\OCSController {

	/** @var CapabilitiesManager */
	private $capabilitiesManager;

	/** @var IUserSession */
	private $userSession;

	/**
	 * OCSController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param CapabilitiesManager $capabilitiesManager
	 * @param IUserSession $userSession
	 */
	public function __construct($appName,
								IRequest $request,
								CapabilitiesManager $capabilitiesManager,
								IUserSession $userSession) {
		parent::__construct($appName, $request);

		$this->capabilitiesManager = $capabilitiesManager;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function getCapabilities() {
		$result = [];
		list($major, $minor, $micro) = \OCP\Util::getVersion();
		$result['version'] = array(
			'major' => $major,
			'minor' => $minor,
			'micro' => $micro,
			'string' => \OC_Util::getVersionString(),
			'edition' => \OC_Util::getEditionString(),
		);

		$result['capabilities'] = $this->capabilitiesManager->getCapabilities();

		return new DataResponse(['data' => $result]);
	}

	/**
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function getCurrentUser() {
		$userObject = $this->userSession->getUser();
		$data  = [
			'id' => $userObject->getUID(),
			'display-name' => $userObject->getDisplayName(),
			'email' => $userObject->getEMailAddress(),
		];
		return new DataResponse(['data' => $data]);
	}
}
