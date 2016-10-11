<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OC\Comments;

use Doctrine\DBAL\Exception\DriverException;
use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Manager implements ICommentsManager {

	/** @var  IDBConnection */
	protected $dbConn;

	/** @var  ILogger */
	protected $logger;

	/** @var IConfig */
	protected $config;

	/** @var EventDispatcherInterface */
	protected $dispatcher;

	/** @var IComment[]  */
	protected $commentsCache = [];

	/**
	 * Manager constructor.
	 *
	 * @param IDBConnection $dbConn
	 * @param ILogger $logger
	 * @param IConfig $config
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function __construct(
		IDBConnection $dbConn,
		ILogger $logger,
		IConfig $config,
		EventDispatcherInterface $dispatcher
	) {
		$this->dbConn = $dbConn;
		$this->logger = $logger;
		$this->config = $config;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * converts data base data into PHP native, proper types as defined by
	 * IComment interface.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function normalizeDatabaseData(array $data) {
		$data['id'] = strval($data['id']);
		$data['parent_id'] = strval($data['parent_id']);
		$data['topmost_parent_id'] = strval($data['topmost_parent_id']);
		$data['creation_timestamp'] = new \DateTime($data['creation_timestamp']);
		if (!is_null($data['latest_child_timestamp'])) {
			$data['latest_child_timestamp'] = new \DateTime($data['latest_child_timestamp']);
		}
		$data['children_count'] = intval($data['children_count']);
		return $data;
	}

	/**
	 * prepares a comment for an insert or update operation after making sure
	 * all necessary fields have a value assigned.
	 *
	 * @param IComment $comment
	 * @return IComment returns the same updated IComment instance as provided
	 *                  by parameter for convenience
	 * @throws \UnexpectedValueException
	 */
	protected function prepareCommentForDatabaseWrite(IComment $comment) {
		if(    !$comment->getActorType()
			|| !$comment->getActorId()
			|| !$comment->getObjectType()
			|| !$comment->getObjectId()
			|| !$comment->getVerb()
		) {
			throw new \UnexpectedValueException('Actor, Object and Verb information must be provided for saving');
		}

		if($comment->getId() === '') {
			$comment->setChildrenCount(0);
			$comment->setLatestChildDateTime(new \DateTime('0000-00-00 00:00:00', new \DateTimeZone('UTC')));
			$comment->setLatestChildDateTime(null);
		}

		if(is_null($comment->getCreationDateTime())) {
			$comment->setCreationDateTime(new \DateTime());
		}

		if($comment->getParentId() !== '0') {
			$comment->setTopmostParentId($this->determineTopmostParentId($comment->getParentId()));
		} else {
			$comment->setTopmostParentId('0');
		}

		$this->cache($comment);

		return $comment;
	}

	/**
	 * returns the topmost parent id of a given comment identified by ID
	 *
	 * @param string $id
	 * @return string
	 * @throws NotFoundException
	 */
	protected function determineTopmostParentId($id) {
		$comment = $this->get($id);
		if($comment->getParentId() === '0') {
			return $comment->getId();
		} else {
			return $this->determineTopmostParentId($comment->getId());
		}
	}

	/**
	 * updates child information of a comment
	 *
	 * @param string	$id
	 * @param \DateTime	$cDateTime	the date time of the most recent child
	 * @throws NotFoundException
	 */
	protected function updateChildrenInformation($id, \DateTime $cDateTime) {
		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->select($qb->createFunction('COUNT(`id`)'))
				->from('comments')
				->where($qb->expr()->eq('parent_id', $qb->createParameter('id')))
				->setParameter('id', $id);

		$resultStatement = $query->execute();
		$data = $resultStatement->fetch(\PDO::FETCH_NUM);
		$resultStatement->closeCursor();
		$children = intval($data[0]);

		$comment = $this->get($id);
		$comment->setChildrenCount($children);
		$comment->setLatestChildDateTime($cDateTime);
		$this->save($comment);
	}

	/**
	 * Tests whether actor or object type and id parameters are acceptable.
	 * Throws exception if not.
	 *
	 * @param string $role
	 * @param string $type
	 * @param string $id
	 * @throws \InvalidArgumentException
	 */
	protected function checkRoleParameters($role, $type, $id) {
		if(
			   !is_string($type) || empty($type)
			|| !is_string($id) || empty($id)
		) {
			throw new \InvalidArgumentException($role . ' parameters must be string and not empty');
		}
	}

	/**
	 * run-time caches a comment
	 *
	 * @param IComment $comment
	 */
	protected function cache(IComment $comment) {
		$id = $comment->getId();
		if(empty($id)) {
			return;
		}
		$this->commentsCache[strval($id)] = $comment;
	}

	/**
	 * removes an entry from the comments run time cache
	 *
	 * @param mixed $id the comment's id
	 */
	protected function uncache($id) {
		$id = strval($id);
		if (isset($this->commentsCache[$id])) {
			unset($this->commentsCache[$id]);
		}
	}

	/**
	 * returns a comment instance
	 *
	 * @param string $id the ID of the comment
	 * @return IComment
	 * @throws NotFoundException
	 * @throws \InvalidArgumentException
	 * @since 9.0.0
	 */
	public function get($id) {
		if(intval($id) === 0) {
			throw new \InvalidArgumentException('IDs must be translatable to a number in this implementation.');
		}

		if(isset($this->commentsCache[$id])) {
			return $this->commentsCache[$id];
		}

		$qb = $this->dbConn->getQueryBuilder();
		$resultStatement = $qb->select('*')
			->from('comments')
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
			->setParameter('id', $id, IQueryBuilder::PARAM_INT)
			->execute();

		$data = $resultStatement->fetch();
		$resultStatement->closeCursor();
		if(!$data) {
			throw new NotFoundException();
		}

		$comment = new Comment($this->normalizeDatabaseData($data));
		$this->cache($comment);
		return $comment;
	}

	/**
	 * returns the comment specified by the id and all it's child comments.
	 * At this point of time, we do only support one level depth.
	 *
	 * @param string $id
	 * @param int $limit max number of entries to return, 0 returns all
	 * @param int $offset the start entry
	 * @return array
	 * @since 9.0.0
	 *
	 * The return array looks like this
	 * [
	 *   'comment' => IComment, // root comment
	 *   'replies' =>
	 *   [
	 *     0 =>
	 *     [
	 *       'comment' => IComment,
	 *       'replies' => []
	 *     ]
	 *     1 =>
	 *     [
	 *       'comment' => IComment,
	 *       'replies'=> []
	 *     ],
	 *     …
	 *   ]
	 * ]
	 */
	public function getTree($id, $limit = 0, $offset = 0) {
		$tree = [];
		$tree['comment'] = $this->get($id);
		$tree['replies'] = [];

		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->select('*')
				->from('comments')
				->where($qb->expr()->eq('topmost_parent_id', $qb->createParameter('id')))
				->orderBy('creation_timestamp', 'DESC')
				->setParameter('id', $id);

		if($limit > 0) {
			$query->setMaxResults($limit);
		}
		if($offset > 0) {
			$query->setFirstResult($offset);
		}

		$resultStatement = $query->execute();
		while($data = $resultStatement->fetch()) {
			$comment = new Comment($this->normalizeDatabaseData($data));
			$this->cache($comment);
			$tree['replies'][] = [
				'comment' => $comment,
				'replies' => []
			];
		}
		$resultStatement->closeCursor();

		return $tree;
	}

	/**
	 * returns comments for a specific object (e.g. a file).
	 *
	 * The sort order is always newest to oldest.
	 *
	 * @param string $objectType the object type, e.g. 'files'
	 * @param string $objectId the id of the object
	 * @param int $limit optional, number of maximum comments to be returned. if
	 * not specified, all comments are returned.
	 * @param int $offset optional, starting point
	 * @param \DateTime $notOlderThan optional, timestamp of the oldest comments
	 * that may be returned
	 * @return IComment[]
	 * @since 9.0.0
	 */
	public function getForObject(
			$objectType,
			$objectId,
			$limit = 0,
			$offset = 0,
			\DateTime $notOlderThan = null
	) {
		$comments = [];

		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->select('*')
				->from('comments')
				->where($qb->expr()->eq('object_type', $qb->createParameter('type')))
				->andWhere($qb->expr()->eq('object_id', $qb->createParameter('id')))
				->orderBy('creation_timestamp', 'DESC')
				->setParameter('type', $objectType)
				->setParameter('id', $objectId);

		if($limit > 0) {
			$query->setMaxResults($limit);
		}
		if($offset > 0) {
			$query->setFirstResult($offset);
		}
		if(!is_null($notOlderThan)) {
			$query
				->andWhere($qb->expr()->gt('creation_timestamp', $qb->createParameter('notOlderThan')))
				->setParameter('notOlderThan', $notOlderThan, 'datetime');
		}

		$resultStatement = $query->execute();
		while($data = $resultStatement->fetch()) {
			$comment = new Comment($this->normalizeDatabaseData($data));
			$this->cache($comment);
			$comments[] = $comment;
		}
		$resultStatement->closeCursor();

		return $comments;
	}

	/**
	 * @param $objectType string the object type, e.g. 'files'
	 * @param $objectId string the id of the object
	 * @param \DateTime $notOlderThan optional, timestamp of the oldest comments
	 * that may be returned
	 * @return Int
	 * @since 9.0.0
	 */
	public function getNumberOfCommentsForObject($objectType, $objectId, \DateTime $notOlderThan = null) {
		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->select($qb->createFunction('COUNT(`id`)'))
				->from('comments')
				->where($qb->expr()->eq('object_type', $qb->createParameter('type')))
				->andWhere($qb->expr()->eq('object_id', $qb->createParameter('id')))
				->setParameter('type', $objectType)
				->setParameter('id', $objectId);

		if(!is_null($notOlderThan)) {
			$query
				->andWhere($qb->expr()->gt('creation_timestamp', $qb->createParameter('notOlderThan')))
				->setParameter('notOlderThan', $notOlderThan, 'datetime');
		}

		$resultStatement = $query->execute();
		$data = $resultStatement->fetch(\PDO::FETCH_NUM);
		$resultStatement->closeCursor();
		return intval($data[0]);
	}

	/**
	 * creates a new comment and returns it. At this point of time, it is not
	 * saved in the used data storage. Use save() after setting other fields
	 * of the comment (e.g. message or verb).
	 *
	 * @param string $actorType the actor type (e.g. 'users')
	 * @param string $actorId a user id
	 * @param string $objectType the object type the comment is attached to
	 * @param string $objectId the object id the comment is attached to
	 * @return IComment
	 * @since 9.0.0
	 */
	public function create($actorType, $actorId, $objectType, $objectId) {
		$comment = new Comment();
		$comment
			->setActor($actorType, $actorId)
			->setObject($objectType, $objectId);
		return $comment;
	}

	/**
	 * permanently deletes the comment specified by the ID
	 *
	 * When the comment has child comments, their parent ID will be changed to
	 * the parent ID of the item that is to be deleted.
	 *
	 * @param string $id
	 * @return bool
	 * @throws \InvalidArgumentException
	 * @since 9.0.0
	 */
	public function delete($id) {
		if(!is_string($id)) {
			throw new \InvalidArgumentException('Parameter must be string');
		}

		try {
			$comment = $this->get($id);
		} catch (\Exception $e) {
			// Ignore exceptions, we just don't fire a hook then
			$comment = null;
		}

		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->delete('comments')
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
			->setParameter('id', $id);

		try {
			$affectedRows = $query->execute();
			$this->uncache($id);
		} catch (DriverException $e) {
			$this->logger->logException($e, ['app' => 'core_comments']);
			return false;
		}

		if ($affectedRows > 0 && $comment instanceof IComment) {
			$this->dispatcher->dispatch(CommentsEvent::EVENT_DELETE, new CommentsEvent(
				CommentsEvent::EVENT_DELETE,
				$comment
			));
		}

		return ($affectedRows > 0);
	}

	/**
	 * saves the comment permanently
	 *
	 * if the supplied comment has an empty ID, a new entry comment will be
	 * saved and the instance updated with the new ID.
	 *
	 * Otherwise, an existing comment will be updated.
	 *
	 * Throws NotFoundException when a comment that is to be updated does not
	 * exist anymore at this point of time.
	 *
	 * @param IComment $comment
	 * @return bool
	 * @throws NotFoundException
	 * @since 9.0.0
	 */
	public function save(IComment $comment) {
		if($this->prepareCommentForDatabaseWrite($comment)->getId() === '') {
			$result = $this->insert($comment);
		} else {
			$result = $this->update($comment);
		}

		if($result && !!$comment->getParentId()) {
			$this->updateChildrenInformation(
					$comment->getParentId(),
					$comment->getCreationDateTime()
			);
			$this->cache($comment);
		}

		return $result;
	}

	/**
	 * inserts the provided comment in the database
	 *
	 * @param IComment $comment
	 * @return bool
	 */
	protected function insert(IComment &$comment) {
		$qb = $this->dbConn->getQueryBuilder();
		$affectedRows = $qb
			->insert('comments')
			->values([
				'parent_id'					=> $qb->createNamedParameter($comment->getParentId()),
				'topmost_parent_id' 		=> $qb->createNamedParameter($comment->getTopmostParentId()),
				'children_count' 			=> $qb->createNamedParameter($comment->getChildrenCount()),
				'actor_type' 				=> $qb->createNamedParameter($comment->getActorType()),
				'actor_id' 					=> $qb->createNamedParameter($comment->getActorId()),
				'message' 					=> $qb->createNamedParameter($comment->getMessage()),
				'verb' 						=> $qb->createNamedParameter($comment->getVerb()),
				'creation_timestamp' 		=> $qb->createNamedParameter($comment->getCreationDateTime(), 'datetime'),
				'latest_child_timestamp'	=> $qb->createNamedParameter($comment->getLatestChildDateTime(), 'datetime'),
				'object_type' 				=> $qb->createNamedParameter($comment->getObjectType()),
				'object_id' 				=> $qb->createNamedParameter($comment->getObjectId()),
			])
			->execute();

		if ($affectedRows > 0) {
			$comment->setId(strval($qb->getLastInsertId()));
		}

		$this->dispatcher->dispatch(CommentsEvent::EVENT_ADD, new CommentsEvent(
			CommentsEvent::EVENT_ADD,
			$comment
		));

		return $affectedRows > 0;
	}

	/**
	 * updates a Comment data row
	 *
	 * @param IComment $comment
	 * @return bool
	 * @throws NotFoundException
	 */
	protected function update(IComment $comment) {
		$qb = $this->dbConn->getQueryBuilder();
		$affectedRows = $qb
			->update('comments')
				->set('parent_id',				$qb->createNamedParameter($comment->getParentId()))
				->set('topmost_parent_id', 		$qb->createNamedParameter($comment->getTopmostParentId()))
				->set('children_count',			$qb->createNamedParameter($comment->getChildrenCount()))
				->set('actor_type', 			$qb->createNamedParameter($comment->getActorType()))
				->set('actor_id', 				$qb->createNamedParameter($comment->getActorId()))
				->set('message',				$qb->createNamedParameter($comment->getMessage()))
				->set('verb',					$qb->createNamedParameter($comment->getVerb()))
				->set('creation_timestamp',		$qb->createNamedParameter($comment->getCreationDateTime(), 'datetime'))
				->set('latest_child_timestamp',	$qb->createNamedParameter($comment->getLatestChildDateTime(), 'datetime'))
				->set('object_type',			$qb->createNamedParameter($comment->getObjectType()))
				->set('object_id',				$qb->createNamedParameter($comment->getObjectId()))
			->where($qb->expr()->eq('id', $qb->createParameter('id')))
			->setParameter('id', $comment->getId())
			->execute();

		if($affectedRows === 0) {
			throw new NotFoundException('Comment to update does ceased to exist');
		}

		$this->dispatcher->dispatch(CommentsEvent::EVENT_UPDATE, new CommentsEvent(
			CommentsEvent::EVENT_UPDATE,
			$comment
		));

		return $affectedRows > 0;
	}

	/**
	 * removes references to specific actor (e.g. on user delete) of a comment.
	 * The comment itself must not get lost/deleted.
	 *
	 * @param string $actorType the actor type (e.g. 'users')
	 * @param string $actorId a user id
	 * @return boolean
	 * @since 9.0.0
	 */
	public function deleteReferencesOfActor($actorType, $actorId) {
		$this->checkRoleParameters('Actor', $actorType, $actorId);

		$qb = $this->dbConn->getQueryBuilder();
		$affectedRows = $qb
			->update('comments')
			->set('actor_type',	$qb->createNamedParameter(ICommentsManager::DELETED_USER))
			->set('actor_id',	$qb->createNamedParameter(ICommentsManager::DELETED_USER))
			->where($qb->expr()->eq('actor_type', $qb->createParameter('type')))
			->andWhere($qb->expr()->eq('actor_id', $qb->createParameter('id')))
			->setParameter('type', $actorType)
			->setParameter('id', $actorId)
			->execute();

		$this->commentsCache = [];

		return is_int($affectedRows);
	}

	/**
	 * deletes all comments made of a specific object (e.g. on file delete)
	 *
	 * @param string $objectType the object type (e.g. 'files')
	 * @param string $objectId e.g. the file id
	 * @return boolean
	 * @since 9.0.0
	 */
	public function deleteCommentsAtObject($objectType, $objectId) {
		$this->checkRoleParameters('Object', $objectType, $objectId);

		$qb = $this->dbConn->getQueryBuilder();
		$affectedRows = $qb
			->delete('comments')
			->where($qb->expr()->eq('object_type', $qb->createParameter('type')))
			->andWhere($qb->expr()->eq('object_id', $qb->createParameter('id')))
			->setParameter('type', $objectType)
			->setParameter('id', $objectId)
			->execute();

		$this->commentsCache = [];

		return is_int($affectedRows);
	}

	/**
	 * deletes the read markers for the specified user
	 *
	 * @param \OCP\IUser $user
	 * @return bool
	 * @since 9.0.0
	 */
	public function deleteReadMarksFromUser(IUser $user) {
		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->delete('comments_read_markers')
			->where($qb->expr()->eq('user_id', $qb->createParameter('user_id')))
			->setParameter('user_id', $user->getUID());

		try {
			$affectedRows = $query->execute();
		} catch (DriverException $e) {
			$this->logger->logException($e, ['app' => 'core_comments']);
			return false;
		}
		return ($affectedRows > 0);
	}

	/**
	 * sets the read marker for a given file to the specified date for the
	 * provided user
	 *
	 * @param string $objectType
	 * @param string $objectId
	 * @param \DateTime $dateTime
	 * @param IUser $user
	 * @since 9.0.0
	 */
	public function setReadMark($objectType, $objectId, \DateTime $dateTime, IUser $user) {
		$this->checkRoleParameters('Object', $objectType, $objectId);

		$qb = $this->dbConn->getQueryBuilder();
		$values = [
			'user_id'         => $qb->createNamedParameter($user->getUID()),
			'marker_datetime' => $qb->createNamedParameter($dateTime, 'datetime'),
			'object_type'     => $qb->createNamedParameter($objectType),
			'object_id'       => $qb->createNamedParameter($objectId),
		];

		// Strategy: try to update, if this does not return affected rows, do an insert.
		$affectedRows = $qb
			->update('comments_read_markers')
			->set('user_id',         $values['user_id'])
			->set('marker_datetime', $values['marker_datetime'])
			->set('object_type',     $values['object_type'])
			->set('object_id',       $values['object_id'])
			->where($qb->expr()->eq('user_id', $qb->createParameter('user_id')))
			->andWhere($qb->expr()->eq('object_type', $qb->createParameter('object_type')))
			->andWhere($qb->expr()->eq('object_id', $qb->createParameter('object_id')))
			->setParameter('user_id', $user->getUID(), IQueryBuilder::PARAM_STR)
			->setParameter('object_type', $objectType, IQueryBuilder::PARAM_STR)
			->setParameter('object_id', $objectId, IQueryBuilder::PARAM_STR)
			->execute();

		if ($affectedRows > 0) {
			return;
		}

		$qb->insert('comments_read_markers')
			->values($values)
			->execute();
	}

	/**
	 * returns the read marker for a given file to the specified date for the
	 * provided user. It returns null, when the marker is not present, i.e.
	 * no comments were marked as read.
	 *
	 * @param string $objectType
	 * @param string $objectId
	 * @param IUser $user
	 * @return \DateTime|null
	 * @since 9.0.0
	 */
	public function getReadMark($objectType, $objectId, IUser $user) {
		$qb = $this->dbConn->getQueryBuilder();
		$resultStatement = $qb->select('marker_datetime')
			->from('comments_read_markers')
			->where($qb->expr()->eq('user_id', $qb->createParameter('user_id')))
			->andWhere($qb->expr()->eq('object_type', $qb->createParameter('object_type')))
			->andWhere($qb->expr()->eq('object_id', $qb->createParameter('object_id')))
			->setParameter('user_id', $user->getUID(), IQueryBuilder::PARAM_STR)
			->setParameter('object_type', $objectType, IQueryBuilder::PARAM_STR)
			->setParameter('object_id', $objectId, IQueryBuilder::PARAM_STR)
			->execute();

		$data = $resultStatement->fetch();
		$resultStatement->closeCursor();
		if(!$data || is_null($data['marker_datetime'])) {
			return null;
		}

		return new \DateTime($data['marker_datetime']);
	}

	/**
	 * deletes the read markers on the specified object
	 *
	 * @param string $objectType
	 * @param string $objectId
	 * @return bool
	 * @since 9.0.0
	 */
	public function deleteReadMarksOnObject($objectType, $objectId) {
		$this->checkRoleParameters('Object', $objectType, $objectId);

		$qb = $this->dbConn->getQueryBuilder();
		$query = $qb->delete('comments_read_markers')
			->where($qb->expr()->eq('object_type', $qb->createParameter('object_type')))
			->andWhere($qb->expr()->eq('object_id', $qb->createParameter('object_id')))
			->setParameter('object_type', $objectType)
			->setParameter('object_id', $objectId);

		try {
			$affectedRows = $query->execute();
		} catch (DriverException $e) {
			$this->logger->logException($e, ['app' => 'core_comments']);
			return false;
		}
		return ($affectedRows > 0);
	}
}
