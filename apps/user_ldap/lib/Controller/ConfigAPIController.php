<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\User_LDAP\Controller;

use OC\CapabilitiesManager;
use OC\Core\Controller\OCSController;
use OC\Security\Bruteforce\Throttler;
use OC\Security\IdentityProof\Manager;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class ConfigAPIController extends OCSController {

	/** @var Helper */
	private $ldapHelper;

	/** @var ILogger */
	private $logger;

	public function __construct(
		$appName,
		IRequest $request,
		CapabilitiesManager $capabilitiesManager,
		IUserSession $userSession,
		IUserManager $userManager,
		Throttler $throttler,
		Manager $keyManager,
		Helper $ldapHelper,
		ILogger $logger
	) {
		parent::__construct(
			$appName,
			$request,
			$capabilitiesManager,
			$userSession,
			$userManager,
			$throttler,
			$keyManager
		);


		$this->ldapHelper = $ldapHelper;
		$this->logger = $logger;
	}

	/**
	 * creates a new (empty) configuration and returns the resulting prefix
	 *
	 * Example: curl -X POST -H "OCS-APIREQUEST: true"  -u $admin:$password \
	 *   https://nextcloud.server/ocs/v1.php/apps/user_ldap/api/v1/config
	 *
	 * results in:
	 *
	 * <?xml version="1.0"?>
	 * <ocs>
	 *   <meta>
	 *     <status>ok</status>
	 *     <statuscode>100</statuscode>
	 *     <message>OK</message>
	 *     <totalitems></totalitems>
	 *     <itemsperpage></itemsperpage>
	 *   </meta>
	 *   <data>
	 *     <configID>s40</configID>
	 *   </data>
	 * </ocs>
	 *
	 * Failing example: if an exception is thrown (e.g. Database connection lost)
	 * the detailed error will be logged. The output will then look like:
	 *
	 * <?xml version="1.0"?>
	 * <ocs>
	 *   <meta>
	 *     <status>failure</status>
	 *     <statuscode>999</statuscode>
	 *     <message>An issue occurred when creating the new config.</message>
	 *     <totalitems></totalitems>
	 *     <itemsperpage></itemsperpage>
	 *   </meta>
	 *   <data/>
	 * </ocs>
	 *
	 * For JSON output provide the format=json parameter
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function create() {
		try {
			$configPrefix = $this->ldapHelper->getNextServerConfigurationPrefix();
			$configHolder = new Configuration($configPrefix);
			$configHolder->saveConfiguration();
		} catch (\Exception $e) {
			$this->logger->logException($e);
			throw new OCSException('An issue occurred when creating the new config.');
		}
		return new DataResponse(['configID' => $configPrefix]);
	}

	/**
	 * Deletes a LDAP configuration, if present.
	 *
	 * Example:
	 *   curl -X DELETE -H "OCS-APIREQUEST: true" -u $admin:$password \
	 *    https://nextcloud.server/ocs/v1.php/apps/user_ldap/api/v1/config/s60
	 *
	 * <?xml version="1.0"?>
	 * <ocs>
	 *   <meta>
	 *     <status>ok</status>
	 *     <statuscode>100</statuscode>
	 *     <message>OK</message>
	 *     <totalitems></totalitems>
	 *     <itemsperpage></itemsperpage>
	 *   </meta>
	 *   <data/>
	 * </ocs>
	 *
	 * @param $configID
	 * @return DataResponse
	 * @throws OCSBadRequestException
	 * @throws OCSException
	 */
	public function delete($configID) {
		$initial = substr($configID, 0, 1);
		$number  = substr($configID, 1);
		if($initial !== 's' || $number !== strval(intval($number))) {
			throw new OCSBadRequestException('Not a valid config ID');
		}

		try {
			$prefixes = $this->ldapHelper->getServerConfigurationPrefixes();
			if(!in_array($configID, $prefixes)) {
				throw new OCSNotFoundException('Config ID not found');
			}
			if(!$this->ldapHelper->deleteServerConfiguration($configID)) {
				throw new OCSException('Could not delete configuration');
			}
		} catch(OCSException $e) {
			throw $e;
		} catch(\Exception $e) {
			$this->logger->logException($e);
			throw new OCSException('An issue occurred when deleting the config.');
		}

		return new DataResponse();
	}
}
