<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Björn Schießle
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


namespace OC\Accounts;

use OCP\IDBConnection;
use OCP\IUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class AccountManager
 *
 * Manage system accounts table
 *
 * @group DB
 * @package OC\Accounts
 */
class AccountManager {

	/** nobody can see my account details */
	const VISIBILITY_PRIVATE = 'private';
	/** only contacts, especially trusted servers can see my contact details */
	const VISIBILITY_CONTACTS_ONLY = 'contacts';
	/** every body ca see my contact detail, will be published to the lookup server */
	const VISIBILITY_PUBLIC = 'public';

	const PROPERTY_AVATAR = 'avatar';
	const PROPERTY_DISPLAYNAME = 'displayname';
	const PROPERTY_PHONE = 'phone';
	const PROPERTY_EMAIL = 'email';
	const PROPERTY_WEBSITE = 'website';
	const PROPERTY_ADDRESS = 'address';
	const PROPERTY_TWITTER = 'twitter';

	/** @var  IDBConnection database connection */
	private $connection;

	/** @var string table name */
	private $table = 'accounts';

	/** @var EventDispatcherInterface */
	private $eventDispatcher;

	/**
	 * AccountManager constructor.
	 *
	 * @param IDBConnection $connection
	 * @param EventDispatcherInterface $eventDispatcher
	 */
	public function __construct(IDBConnection $connection, EventDispatcherInterface $eventDispatcher) {
		$this->connection = $connection;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * update user record
	 *
	 * @param IUser $user
	 * @param $data
	 */
	public function updateUser(IUser $user, $data) {
		$userData = $this->getUser($user);
		$updated = true;
		if (empty($userData)) {
			$this->insertNewUser($user, $data);
		} elseif ($userData !== $data) {
			$this->updateExistingUser($user, $data);
		} else {
			// nothing needs to be done if new and old data set are the same
			$updated = false;
		}

		if ($updated) {
			$this->eventDispatcher->dispatch(
				'OC\AccountManager::userUpdated',
				new GenericEvent($user)
			);
		}
	}

	/**
	 * get stored data from a given user
	 *
	 * @param IUser $user
	 * @return array
	 */
	public function getUser(IUser $user) {
		$uid = $user->getUID();
		$query = $this->connection->getQueryBuilder();
		$query->select('data')->from($this->table)
			->where($query->expr()->eq('uid', $query->createParameter('uid')))
			->setParameter('uid', $uid);
		$query->execute();
		$result = $query->execute()->fetchAll();

		if (empty($result)) {
			$userData = $this->buildDefaultUserRecord($user);
			$this->insertNewUser($user, $userData);
			return $userData;
		}

		return json_decode($result[0]['data'], true);
	}

	/**
	 * add new user to accounts table
	 *
	 * @param IUser $user
	 * @param array $data
	 */
	protected function insertNewUser(IUser $user, $data) {
		$uid = $user->getUID();
		$jsonEncodedData = json_encode($data);
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values(
				[
					'uid' => $query->createNamedParameter($uid),
					'data' => $query->createNamedParameter($jsonEncodedData),
				]
			)
			->execute();
	}

	/**
	 * update existing user in accounts table
	 *
	 * @param IUser $user
	 * @param array $data
	 */
	protected function updateExistingUser(IUser $user, $data) {
		$uid = $user->getUID();
		$jsonEncodedData = json_encode($data);
		$query = $this->connection->getQueryBuilder();
		$query->update($this->table)
			->set('data', $query->createNamedParameter($jsonEncodedData))
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->execute();
	}

	/**
	 * build default user record in case not data set exists yet
	 *
	 * @param IUser $user
	 * @return array
	 */
	protected function buildDefaultUserRecord(IUser $user) {
		return [
			self::PROPERTY_DISPLAYNAME =>
				[
					'value' => $user->getDisplayName(),
					'scope' => self::VISIBILITY_CONTACTS_ONLY,
				],
			self::PROPERTY_ADDRESS =>
				[
					'value' => '',
					'scope' => self::VISIBILITY_PRIVATE,
				],
			self::PROPERTY_WEBSITE =>
				[
					'value' => '',
					'scope' => self::VISIBILITY_PRIVATE,
				],
			self::PROPERTY_EMAIL =>
				[
					'value' => $user->getEMailAddress(),
					'scope' => self::VISIBILITY_CONTACTS_ONLY,
				],
			self::PROPERTY_AVATAR =>
				[
					'scope' => self::VISIBILITY_CONTACTS_ONLY
				],
			self::PROPERTY_PHONE =>
				[
					'value' => '',
					'scope' => self::VISIBILITY_PRIVATE,
				],
			self::PROPERTY_TWITTER =>
				[
					'value' => '',
					'scope' => self::VISIBILITY_PRIVATE,
				],
		];
	}

}
