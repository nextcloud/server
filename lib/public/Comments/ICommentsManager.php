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
namespace OCP\Comments;

/**
 * Interface ICommentsManager
 *
 * This class manages the access to comments
 *
 * @package OCP\Comments
 * @since 9.0.0
 */
interface ICommentsManager {

	/**
	 * @const DELETED_USER type and id for a user that has been deleted
	 * @see deleteReferencesOfActor
	 * @since 9.0.0
	 *
	 * To be used as replacement for user type actors in deleteReferencesOfActor().
	 *
	 * User interfaces shall show "Deleted user" as display name, if needed.
	 */
	const DELETED_USER = 'deleted_users';

	/**
	 * returns a comment instance
	 *
	 * @param string $id the ID of the comment
	 * @return IComment
	 * @throws NotFoundException
	 * @since 9.0.0
	 */
	public function get($id);

	/**
	 * returns the comment specified by the id and all it's child comments
	 *
	 * @param string $id
	 * @param int $limit max number of entries to return, 0 returns all
	 * @param int $offset the start entry
	 * @return array
	 * @since 9.0.0
	 *
	 * The return array looks like this
	 * [
	 * 	 'comment' => IComment, // root comment
	 *   'replies' =>
	 *   [
	 *     0 =>
	 *     [
	 *       'comment' => IComment,
	 *       'replies' =>
	 *       [
	 *         0 =>
	 *         [
	 *           'comment' => IComment,
	 *           'replies' => [ … ]
	 *         ],
	 *         …
	 *       ]
	 *     ]
	 *     1 =>
	 *     [
	 *       'comment' => IComment,
	 *       'replies'=> [ … ]
	 *     ],
	 *     …
	 *   ]
	 * ]
	 */
	public function getTree($id, $limit = 0, $offset = 0);

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
	);

	/**
	 * @param $objectType string the object type, e.g. 'files'
	 * @param $objectId string the id of the object
	 * @param \DateTime $notOlderThan optional, timestamp of the oldest comments
	 * that may be returned
	 * @return Int
	 * @since 9.0.0
	 */
	public function getNumberOfCommentsForObject($objectType, $objectId, \DateTime $notOlderThan = null);

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
	public function create($actorType, $actorId, $objectType, $objectId);

	/**
	 * permanently deletes the comment specified by the ID
	 *
	 * When the comment has child comments, their parent ID will be changed to
	 * the parent ID of the item that is to be deleted.
	 *
	 * @param string $id
	 * @return bool
	 * @since 9.0.0
	 */
	public function delete($id);

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
	public function save(IComment $comment);

	/**
	 * removes references to specific actor (e.g. on user delete) of a comment.
	 * The comment itself must not get lost/deleted.
	 *
	 * A 'users' type actor (type and id) should get replaced by the
	 * value of the DELETED_USER constant of this interface.
	 *
	 * @param string $actorType the actor type (e.g. 'users')
	 * @param string $actorId a user id
	 * @return boolean
	 * @since 9.0.0
	 */
	public function deleteReferencesOfActor($actorType, $actorId);

	/**
	 * deletes all comments made of a specific object (e.g. on file delete)
	 *
	 * @param string $objectType the object type (e.g. 'files')
	 * @param string $objectId e.g. the file id
	 * @return boolean
	 * @since 9.0.0
	 */
	public function deleteCommentsAtObject($objectType, $objectId);

	/**
	 * sets the read marker for a given file to the specified date for the
	 * provided user
	 *
	 * @param string $objectType
	 * @param string $objectId
	 * @param \DateTime $dateTime
	 * @param \OCP\IUser $user
	 * @since 9.0.0
	 */
	public function setReadMark($objectType, $objectId, \DateTime $dateTime, \OCP\IUser $user);

	/**
	 * returns the read marker for a given file to the specified date for the
	 * provided user. It returns null, when the marker is not present, i.e.
	 * no comments were marked as read.
	 *
	 * @param string $objectType
	 * @param string $objectId
	 * @param \OCP\IUser $user
	 * @return \DateTime|null
	 * @since 9.0.0
	 */
	public function getReadMark($objectType, $objectId, \OCP\IUser $user);

	/**
	 * deletes the read markers for the specified user
	 *
	 * @param \OCP\IUser $user
	 * @return bool
	 * @since 9.0.0
	 */
	public function deleteReadMarksFromUser(\OCP\IUser $user);

	/**
	 * deletes the read markers on the specified object
	 *
	 * @param string $objectType
	 * @param string $objectId
	 * @return bool
	 * @since 9.0.0
	 */
	public function deleteReadMarksOnObject($objectType, $objectId);

	/**
	 * registers an Entity to the manager, so event notifications can be send
	 * to consumers of the comments infrastructure
	 *
	 * @param \Closure $closure
	 * @since 9.2.0
	 */
	public function registerEventHandler(\Closure $closure);

}
