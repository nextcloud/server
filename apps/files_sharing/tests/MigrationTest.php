<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Tests;


use OCA\Files_Sharing\Migration;
use OCP\Share;

/**
 * Class MigrationTest
 *
 * @group DB
 */
class MigrationTest extends TestCase {

	/** @var \OCP\IDBConnection */
	private $connection;

	/** @var \OCP\IConfig  */
	private $config;

	/** @var Migration */
	private $migration;

	private $table = 'share';

	public function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->config = \OC::$server->getConfig();
		$this->migration = new Migration($this->connection, $this->config);

		$this->cleanDB();
	}

	public function tearDown() {
		parent::tearDown();
		$this->cleanDB();
	}

	private function cleanDB() {
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)->execute();
	}

	public function verifyResult() {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from($this->table)->orderBy('id');
		$result = $query->execute()->fetchAll();
		$this->assertSame(10, count($result));

		// shares which shouldn't be modified
		for ($i = 0; $i < 4; $i++) {
			$this->assertSame('owner1', $result[$i]['uid_owner']);
			$this->assertEmpty($result[$i]['uid_initiator']);
			$this->assertNull($result[$i]['parent']);
		}
		// group share with unique target
		$this->assertSame('owner1', $result[4]['uid_owner']);
		$this->assertEmpty($result[4]['uid_initiator']);
		$this->assertNotEmpty($result[4]['parent']);
		// initial user share which was re-shared
		$this->assertSame('owner2', $result[5]['uid_owner']);
		$this->assertEmpty($result[5]['uid_initiator']);
		$this->assertNull($result[5]['parent']);
		// flatted re-shares
		for($i = 6; $i < 9; $i++) {
			$this->assertSame('owner2', $result[$i]['uid_owner']);
			$user = 'user' . ($i - 5);
			$this->assertSame($user, $result[$i]['uid_initiator']);
			$this->assertNull($result[$i]['parent']);
		}

		/*
		 * The link share is flattend but has an owner to avoid invisible shares
		 * see: https://github.com/owncloud/core/pull/22317
		 */
		$this->assertSame('owner2', $result[9]['uid_owner']);
		$this->assertSame('user3', $result[9]['uid_initiator']);
		$this->assertSame($result[7]['id'], $result[9]['parent']);
	}

	/**
	 * test that we really remove the "shareapi_allow_mail_notification" setting only
	 */
	public function testRemoveSendMailOption() {
		$this->config->setAppValue('core', 'shareapi_setting1', 'dummy-value');
		$this->config->setAppValue('core', 'shareapi_allow_mail_notification', 'no');
		$this->config->setAppValue('core', 'shareapi_allow_public_notification', 'no');

		$this->migration->removeSendMailOption();

		$this->assertNull(
			$this->config->getAppValue('core', 'shareapi_allow_mail_notification', null)
		);
		$this->assertNull(
			$this->config->getAppValue('core', 'shareapi_allow_public_notification', null)
		);

		$this->assertSame('dummy-value',
			$this->config->getAppValue('core', 'shareapi_setting1', null)
		);
	}

	public function testAddPasswordColumn() {

		$shareTypes = [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE, Share::SHARE_TYPE_EMAIL, Share::SHARE_TYPE_LINK];

		foreach ($shareTypes as $shareType) {

			for ($i = 0; $i < 5; $i++) {
				$query = $this->connection->getQueryBuilder();
				$query->insert($this->table)
					->values(
						[
							'share_type' => $query->createParameter('share_type'),
							'share_with' => $query->createParameter('share_with'),
							'uid_owner' => $query->createParameter('uid_owner'),
							'uid_initiator' => $query->createParameter('uid_initiator'),
							'parent' => $query->createParameter('parent'),
							'item_type' => $query->createParameter('item_type'),
							'item_source' => $query->createParameter('item_source'),
							'item_target' => $query->createParameter('item_target'),
							'file_source' => $query->createParameter('file_source'),
							'file_target' => $query->createParameter('file_target'),
							'permissions' => $query->createParameter('permissions'),
							'stime' => $query->createParameter('stime'),
						]
					)
					->setParameter('share_type', $shareType)
					->setParameter('share_with', 'shareWith')
					->setParameter('uid_owner', 'user' . ($i))
					->setParameter('uid_initiator', null)
					->setParameter('parent', 0)
					->setParameter('item_type', 'file')
					->setParameter('item_source', '2')
					->setParameter('item_target', '/2')
					->setParameter('file_source', 2)
					->setParameter('file_target', '/foobar')
					->setParameter('permissions', 31)
					->setParameter('stime', time());

				$this->assertSame(1, $query->execute());
			}
		}

		$this->migration->addPasswordColumn();

		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from('share');
		$allShares = $query->execute()->fetchAll();

		foreach ($allShares as $share) {
			if ((int)$share['share_type'] === Share::SHARE_TYPE_LINK) {
				$this->assertNull( $share['share_with']);
				$this->assertSame('shareWith', $share['password']);
			} else {
				$this->assertSame('shareWith', $share['share_with']);
				$this->assertNull($share['password']);
			}
		}
	}
}
