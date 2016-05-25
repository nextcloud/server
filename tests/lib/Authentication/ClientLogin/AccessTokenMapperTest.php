<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
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

namespace Test\Authentication\ClientLogin;

use OC;
use OC\Authentication\ClientLogin\AccessTokenMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Test\TestCase;

/**
 * @group DB
 */
class AccessTokenMapperTest extends TestCase {

	/** @var AccessTokenMapper */
	private $mapper;

	protected function setUp() {
		parent::setUp();

		$this->db = OC::$server->getDatabaseConnection();
		$this->resetDatabase();

		$this->mapper = new AccessTokenMapper($this->db);
	}

	private function resetDatabase() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('client_access_tokens')->execute();
		$qb->insert('client_access_tokens')->values([
			'token' => $qb->createNamedParameter('9c5a2e661482b65597408a6bb6c4a3d1af36337381872ac56e445a06cdb7fea2b1039db707545c11027a4966919918b19d875a8b774840b18c6cbb7ae56fe206'),
			'uid' => $qb->createNamedParameter('user1'),
			'client_name' => $qb->createNamedParameter('ownCloud Android'),
			'status' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
			'created_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
		])->execute();
		$qb->insert('client_access_tokens')->values([
			'token' => $qb->createNamedParameter('1504445f1524fc801035448a95681a9378ba2e83930c814546c56e5d6ebde221198792fd900c88ed5ead0555780dad1ebce3370d7e154941cd5de87eb419899b'),
			'uid' => $qb->createNamedParameter('user2'),
			'client_name' => $qb->createNamedParameter('ownCloud Sync Client'),
			'status' => $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT),
			'created_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
		])->execute();
	}

	public function testGetToken() {
		$accessToken = '9c5a2e661482b65597408a6bb6c4a3d1af36337381872ac56e445a06cdb7fea2b1039db707545c11027a4966919918b19d875a8b774840b18c6cbb7ae56fe206';

		$this->assertNotNull($this->mapper->getToken($accessToken));
	}

	/**
	 * @expectedException \OCP\AppFramework\Db\DoesNotExistException
	 */
	public function testGetTokenDoesNotExist() {
		$accessToken = 'doesnotexist';

		$this->mapper->getToken($accessToken);
	}

}
