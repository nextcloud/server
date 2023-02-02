<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\User_LDAP\Mapping;

use OCP\HintException;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Server;
use OCP\Support\Subscription\IAssertion;

/**
 * Class UserMapping
 *
 * @package OCA\User_LDAP\Mapping
 */
class UserMapping extends AbstractMapping {

	private IAssertion $assertion;
	protected const PROV_API_REGEX = '/\/ocs\/v[1-9].php\/cloud\/(groups|users)/';

	public function __construct(IDBConnection $dbc, IAssertion $assertion) {
		$this->assertion = $assertion;
		parent::__construct($dbc);
	}

	/**
	 * @throws HintException
	 */
	public function map($fdn, $name, $uuid): bool {
		try {
			$this->assertion->createUserIsLegit();
		} catch (HintException $e) {
			static $isProvisioningApi = null;

			if ($isProvisioningApi === null) {
				$request = Server::get(IRequest::class);
				$isProvisioningApi = \preg_match(self::PROV_API_REGEX, $request->getRequestUri()) === 1;
			}
			if ($isProvisioningApi) {
				// only throw when prov API is being used, since functionality
				// should not break for end users (e.g. when sharing).
				// On direct API usage, e.g. on users page, this is desired.
				throw $e;
			}
			return false;
		}
		return parent::map($fdn, $name, $uuid);
	}

	/**
	 * returns the DB table name which holds the mappings
	 * @return string
	 */
	protected function getTableName(bool $includePrefix = true) {
		$p = $includePrefix ? '*PREFIX*' : '';
		return $p . 'ldap_user_mapping';
	}
}
