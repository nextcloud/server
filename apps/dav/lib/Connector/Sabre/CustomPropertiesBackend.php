<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\DAV\Connector\Sabre;

use OCP\IDBConnection;
use OCP\IUser;
use Sabre\DAV\PropertyStorage\Backend\BackendInterface;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Tree;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;

class CustomPropertiesBackend implements BackendInterface {

	/**
	 * Ignored properties
	 *
	 * @var array
	 */
	private $ignoredProperties = array(
		'{DAV:}getcontentlength',
		'{DAV:}getcontenttype',
		'{DAV:}getetag',
		'{DAV:}quota-used-bytes',
		'{DAV:}quota-available-bytes',
		'{DAV:}quota-available-bytes',
		'{http://owncloud.org/ns}permissions',
		'{http://owncloud.org/ns}downloadURL',
		'{http://owncloud.org/ns}dDC',
		'{http://owncloud.org/ns}size',
	);

	/**
	 * @var Tree
	 */
	private $tree;

	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var IUser
	 */
	private $user;

	/**
	 * Properties cache
	 *
	 * @var array
	 */
	private $cache = [];

	/**
	 * @param Tree $tree node tree
	 * @param IDBConnection $connection database connection
	 * @param IUser $user owner of the tree and properties
	 */
	public function __construct(
		Tree $tree,
		IDBConnection $connection,
		IUser $user) {
		$this->tree = $tree;
		$this->connection = $connection;
		$this->user = $user->getUID();
	}

	/**
	 * Fetches properties for a path.
	 *
	 * @param string $path
	 * @param PropFind $propFind
	 * @return void
	 */
	public function propFind($path, PropFind $propFind) {
		try {
			$node = $this->tree->getNodeForPath($path);
			if (!($node instanceof Node)) {
				return;
			}
		} catch (ServiceUnavailable $e) {
			// might happen for unavailable mount points, skip
			return;
		} catch (NotFound $e) {
			// in some rare (buggy) cases the node might not be found,
			// we catch the exception to prevent breaking the whole list with a 404
			// (soft fail)
			\OC::$server->getLogger()->warning(
				'Could not get node for path: \"' . $path . '\" : ' . $e->getMessage(),
				array('app' => 'files')
			);
			return;
		}

		$requestedProps = $propFind->get404Properties();

		// these might appear
		$requestedProps = array_diff(
			$requestedProps,
			$this->ignoredProperties
		);

		if (empty($requestedProps)) {
			return;
		}

		if ($node instanceof Directory
			&& $propFind->getDepth() !== 0
		) {
			// note: pre-fetching only supported for depth <= 1
			$this->loadChildrenProperties($node, $requestedProps);
		}

		$props = $this->getProperties($node, $requestedProps);
		foreach ($props as $propName => $propValue) {
			$propFind->set($propName, $propValue);
		}
	}

	/**
	 * Updates properties for a path
	 *
	 * @param string $path
	 * @param PropPatch $propPatch
	 *
	 * @return void
	 */
	public function propPatch($path, PropPatch $propPatch) {
		$node = $this->tree->getNodeForPath($path);
		if (!($node instanceof Node)) {
			return;
		}

		$propPatch->handleRemaining(function($changedProps) use ($node) {
			return $this->updateProperties($node, $changedProps);
		});
	}

	/**
	 * This method is called after a node is deleted.
	 *
	 * @param string $path path of node for which to delete properties
	 */
	public function delete($path) {
		$statement = $this->connection->prepare(
			'DELETE FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?'
		);
		$statement->execute(array($this->user, '/' . $path));
		$statement->closeCursor();

		unset($this->cache[$path]);
	}

	/**
	 * This method is called after a successful MOVE
	 *
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function move($source, $destination) {
		$statement = $this->connection->prepare(
			'UPDATE `*PREFIX*properties` SET `propertypath` = ?' .
			' WHERE `userid` = ? AND `propertypath` = ?'
		);
		$statement->execute(array('/' . $destination, $this->user, '/' . $source));
		$statement->closeCursor();
	}

	/**
	 * Returns a list of properties for this nodes.;
	 * @param Node $node
	 * @param array $requestedProperties requested properties or empty array for "all"
	 * @return array
	 * @note The properties list is a list of propertynames the client
	 * requested, encoded as xmlnamespace#tagName, for example:
	 * http://www.example.org/namespace#author If the array is empty, all
	 * properties should be returned
	 */
	private function getProperties(Node $node, array $requestedProperties) {
		$path = $node->getPath();
		if (isset($this->cache[$path])) {
			return $this->cache[$path];
		}

		// TODO: chunking if more than 1000 properties
		$sql = 'SELECT * FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?';

		$whereValues = array($this->user, $path);
		$whereTypes = array(null, null);

		if (!empty($requestedProperties)) {
			// request only a subset
			$sql .= ' AND `propertyname` in (?)';
			$whereValues[] = $requestedProperties;
			$whereTypes[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
		}

		$result = $this->connection->executeQuery(
			$sql,
			$whereValues,
			$whereTypes
		);

		$props = [];
		while ($row = $result->fetch()) {
			$props[$row['propertyname']] = $row['propertyvalue'];
		}

		$result->closeCursor();

		$this->cache[$path] = $props;
		return $props;
	}

	/**
	 * Update properties
	 *
	 * @param Node $node node for which to update properties
	 * @param array $properties array of properties to update
	 *
	 * @return bool
	 */
	private function updateProperties($node, $properties) {
		$path = $node->getPath();

		$deleteStatement = 'DELETE FROM `*PREFIX*properties`' .
			' WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?';

		$insertStatement = 'INSERT INTO `*PREFIX*properties`' .
			' (`userid`,`propertypath`,`propertyname`,`propertyvalue`) VALUES(?,?,?,?)';

		$updateStatement = 'UPDATE `*PREFIX*properties` SET `propertyvalue` = ?' .
			' WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?';

		// TODO: use "insert or update" strategy ?
		$existing = $this->getProperties($node, array());
		$this->connection->beginTransaction();
		foreach ($properties as $propertyName => $propertyValue) {
			// If it was null, we need to delete the property
			if (is_null($propertyValue)) {
				if (array_key_exists($propertyName, $existing)) {
					$this->connection->executeUpdate($deleteStatement,
						array(
							$this->user,
							$path,
							$propertyName
						)
					);
				}
			} else {
				if (!array_key_exists($propertyName, $existing)) {
					$this->connection->executeUpdate($insertStatement,
						array(
							$this->user,
							$path,
							$propertyName,
							$propertyValue
						)
					);
				} else {
					$this->connection->executeUpdate($updateStatement,
						array(
							$propertyValue,
							$this->user,
							$path,
							$propertyName
						)
					);
				}
			}
		}

		$this->connection->commit();
		unset($this->cache[$path]);

		return true;
	}

	/**
	 * Bulk load properties for directory children
	 *
	 * @param Directory $node
	 * @param array $requestedProperties requested properties
	 *
	 * @return void
	 */
	private function loadChildrenProperties(Directory $node, $requestedProperties) {
		$path = $node->getPath();
		if (isset($this->cache[$path])) {
			// we already loaded them at some point
			return;
		}

		$childNodes = $node->getChildren();
		// pre-fill cache
		foreach ($childNodes as $childNode) {
			$this->cache[$childNode->getPath()] = [];
		}

		$sql = 'SELECT * FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` LIKE ?';
		$sql .= ' AND `propertyname` in (?) ORDER BY `propertypath`, `propertyname`';

		$result = $this->connection->executeQuery(
			$sql,
			array($this->user, $this->connection->escapeLikeParameter(rtrim($path, '/')) . '/%', $requestedProperties),
			array(null, null, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
		);

		$oldPath = null;
		$props = [];
		while ($row = $result->fetch()) {
			$path = $row['propertypath'];
			if ($oldPath !== $path) {
				// save previously gathered props
				$this->cache[$oldPath] = $props;
				$oldPath = $path;
				// prepare props for next path
				$props = [];
			}
			$props[$row['propertyname']] = $row['propertyvalue'];
		}
		if (!is_null($oldPath)) {
			// save props from last run
			$this->cache[$oldPath] = $props;
		}

		$result->closeCursor();
	}

}
