<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Mapping;

use OCP\HintException;
use OCP\IAppConfig;
use OCP\ICacheFactory;
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

	protected const PROV_API_REGEX = '/\/ocs\/v[1-9].php\/cloud\/(groups|users)/';

	public function __construct(
		IDBConnection $dbc,
		ICacheFactory $cacheFactory,
		IAppConfig $config,
		bool $isCLI,
		private IAssertion $assertion,
	) {
		parent::__construct($dbc, $cacheFactory, $config, $isCLI);
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
