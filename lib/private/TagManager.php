<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Reiter <ockham@raz.or.at>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OC;

use OC\Tagging\TagMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IDBConnection;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IUserSession;
use OCP\User\Events\UserDeletedEvent;
use OCP\Db\Exception as DBException;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserDeletedEvent>
 */
class TagManager implements ITagManager, IEventListener {
	private TagMapper $mapper;
	private IUserSession $userSession;
	private IDBConnection $connection;
	private LoggerInterface $logger;

	public function __construct(TagMapper $mapper, IUserSession $userSession, IDBConnection $connection, LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->userSession = $userSession;
		$this->connection = $connection;
		$this->logger = $logger;
	}

	/**
	 * Create a new \OCP\ITags instance and load tags from db.
	 *
	 * @see \OCP\ITags
	 * @param string $type The type identifier e.g. 'contact' or 'event'.
	 * @param array $defaultTags An array of default tags to be used if none are stored.
	 * @param boolean $includeShared Whether to include tags for items shared with this user by others.
	 * @param string $userId user for which to retrieve the tags, defaults to the currently
	 * logged in user
	 * @return \OCP\ITags
	 *
	 * since 20.0.0 $includeShared isn't used anymore
	 */
	public function load($type, $defaultTags = [], $includeShared = false, $userId = null) {
		if (is_null($userId)) {
			$user = $this->userSession->getUser();
			if ($user === null) {
				// nothing we can do without a user
				return null;
			}
			$userId = $this->userSession->getUser()->getUId();
		}
		return new Tags($this->mapper, $userId, $type, $this->logger, $this->connection, $defaultTags);
	}

	/**
	 * Get all users who favorited an object
	 *
	 * @param string $objectType
	 * @param int $objectId
	 * @return array
	 */
	public function getUsersFavoritingObject(string $objectType, int $objectId): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('uid')
			->from('vcategory_to_object', 'o')
			->innerJoin('o', 'vcategory', 'c', $query->expr()->eq('o.categoryid', 'c.id'))
			->where($query->expr()->eq('objid', $query->createNamedParameter($objectId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('c.type', $query->createNamedParameter($objectType)))
			->andWhere($query->expr()->eq('c.category', $query->createNamedParameter(ITags::TAG_FAVORITE)));

		$result = $query->execute();
		$users = $result->fetchAll(\PDO::FETCH_COLUMN);
		$result->closeCursor();

		return $users;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		// Find all objectid/tagId pairs.
		$user = $event->getUser();
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id')
			->from('vcategory')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($user->getUID())));
		try {
			$result = $qb->executeQuery();
		} catch (DBException $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'core',
				'exception' => $e,
			]);
			return;
		}

		$tagsIds = array_map(fn (array $row) => (int)$row['id'], $result->fetchAll());
		$result->closeCursor();

		if (count($tagsIds) === 0) {
			return;
		}

		// Clean vcategory_to_object table
		$qb = $this->connection->getQueryBuilder();
		$qb = $qb->delete('vcategory_to_object')
			->where($qb->expr()->in('categoryid', $qb->createParameter('chunk')));

		// Clean vcategory
		$qb1 = $this->connection->getQueryBuilder();
		$qb1 = $qb1->delete('vcategory')
			->where($qb1->expr()->in('uid', $qb1->createParameter('chunk')));

		foreach (array_chunk($tagsIds, 1000) as $tagChunk) {
			$qb->setParameter('chunk', $tagChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$qb1->setParameter('chunk', $tagChunk, IQueryBuilder::PARAM_INT_ARRAY);
			try {
				$qb->executeStatement();
				$qb1->executeStatement();
			} catch (DBException $e) {
				$this->logger->error($e->getMessage(), [
					'app' => 'core',
					'exception' => $e,
				]);
			}
		}
	}
}
