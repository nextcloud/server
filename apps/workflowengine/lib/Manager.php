<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\WorkflowEngine;


use OC\Files\Storage\Wrapper\Jail;
use OCP\AppFramework\QueryException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;

class Manager implements IManager {

	/** @var IStorage */
	protected $storage;

	/** @var string */
	protected $path;

	/** @var array[] */
	protected $operations = [];

	/** @var array[] */
	protected $checks = [];

	/** @var IDBConnection */
	protected $connection;

	/** @var IServerContainer|\OC\Server */
	protected $container;

	/** @var IL10N */
	protected $l;

	/**
	 * @param IDBConnection $connection
	 * @param IServerContainer $container
	 * @param IL10N $l
	 */
	public function __construct(IDBConnection $connection, IServerContainer $container, IL10N $l) {
		$this->connection = $connection;
		$this->container = $container;
		$this->l = $l;
	}

	/**
	 * @inheritdoc
	 */
	public function setFileInfo(IStorage $storage, $path) {
		$this->storage = $storage;

		if ($storage->instanceOfStorage(Jail::class)) {
			$path = $storage->getJailedPath($path);
		}
		$this->path = $path;
	}

	/**
	 * @inheritdoc
	 */
	public function getMatchingOperations($class, $returnFirstMatchingOperationOnly = true) {
		$operations = $this->getOperations($class);

		$matches = [];
		foreach ($operations as $operation) {
			$checkIds = json_decode($operation['checks'], true);
			$checks = $this->getChecks($checkIds);

			foreach ($checks as $check) {
				if (!$this->check($check)) {
					// Check did not match, continue with the next operation
					continue 2;
				}
			}

			if ($returnFirstMatchingOperationOnly) {
				return $operation;
			}
			$matches[] = $operation;
		}

		return $matches;
	}

	/**
	 * @param array $check
	 * @return bool
	 */
	protected function check(array $check) {
		try {
			$checkInstance = $this->container->query($check['class']);
		} catch (QueryException $e) {
			// Check does not exist, assume it matches.
			return true;
		}

		if ($checkInstance instanceof ICheck) {
			$checkInstance->setFileInfo($this->storage, $this->path);
			return $checkInstance->executeCheck($check['operator'], $check['value']);
		} else {
			// Check is invalid
			throw new \UnexpectedValueException($this->l->t('Check %s is invalid or does not exist', $check['class']));
		}
	}

	/**
	 * @param string $class
	 * @return array[]
	 */
	public function getOperations($class) {
		if (isset($this->operations[$class])) {
			return $this->operations[$class];
		}

		$query = $this->connection->getQueryBuilder();

		$query->select('*')
			->from('flow_operations')
			->where($query->expr()->eq('class', $query->createNamedParameter($class)));
		$result = $query->execute();

		$this->operations[$class] = [];
		while ($row = $result->fetch()) {
			$this->operations[$class][] = $row;
		}
		$result->closeCursor();

		return $this->operations[$class];
	}

	/**
	 * @param int $id
	 * @return array
	 * @throws \UnexpectedValueException
	 */
	protected function getOperation($id) {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('flow_operations')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			return $row;
		}

		throw new \UnexpectedValueException($this->l->t('Operation #%s does not exist', [$id]));
	}

	/**
	 * @param string $class
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @return array The added operation
	 * @throws \UnexpectedValueException
	 */
	public function addOperation($class, $name, array $checks, $operation) {
		$this->validateOperation($class, $name, $checks, $operation);

		$checkIds = [];
		foreach ($checks as $check) {
			$checkIds[] = $this->addCheck($check['class'], $check['operator'], $check['value']);
		}

		$query = $this->connection->getQueryBuilder();
		$query->insert('flow_operations')
			->values([
				'class' => $query->createNamedParameter($class),
				'name' => $query->createNamedParameter($name),
				'checks' => $query->createNamedParameter(json_encode(array_unique($checkIds))),
				'operation' => $query->createNamedParameter($operation),
			]);
		$query->execute();

		$id = $query->getLastInsertId();
		return $this->getOperation($id);
	}

	/**
	 * @param int $id
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @return array The updated operation
	 * @throws \UnexpectedValueException
	 */
	public function updateOperation($id, $name, array $checks, $operation) {
		$row = $this->getOperation($id);
		$this->validateOperation($row['class'], $name, $checks, $operation);

		$checkIds = [];
		foreach ($checks as $check) {
			$checkIds[] = $this->addCheck($check['class'], $check['operator'], $check['value']);
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('flow_operations')
			->set('name', $query->createNamedParameter($name))
			->set('checks', $query->createNamedParameter(json_encode(array_unique($checkIds))))
			->set('operation', $query->createNamedParameter($operation))
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$query->execute();

		return $this->getOperation($id);
	}

	/**
	 * @param int $id
	 * @return bool
	 * @throws \UnexpectedValueException
	 */
	public function deleteOperation($id) {
		$query = $this->connection->getQueryBuilder();
		$query->delete('flow_operations')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		return (bool) $query->execute();
	}

	/**
	 * @param string $class
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @throws \UnexpectedValueException
	 */
	protected function validateOperation($class, $name, array $checks, $operation) {
		try {
			/** @var IOperation $instance */
			$instance = $this->container->query($class);
		} catch (QueryException $e) {
			throw new \UnexpectedValueException($this->l->t('Operation %s does not exist', [$class]));
		}

		if (!($instance instanceof IOperation)) {
			throw new \UnexpectedValueException($this->l->t('Operation %s is invalid', [$class]));
		}

		$instance->validateOperation($name, $checks, $operation);

		foreach ($checks as $check) {
			try {
				/** @var ICheck $instance */
				$instance = $this->container->query($check['class']);
			} catch (QueryException $e) {
				throw new \UnexpectedValueException($this->l->t('Check %s does not exist', [$class]));
			}

			if (!($instance instanceof ICheck)) {
				throw new \UnexpectedValueException($this->l->t('Check %s is invalid', [$class]));
			}

			$instance->validateCheck($check['operator'], $check['value']);
		}
	}

	/**
	 * @param int[] $checkIds
	 * @return array[]
	 */
	public function getChecks(array $checkIds) {
		$checkIds = array_map('intval', $checkIds);

		$checks = [];
		foreach ($checkIds as $i => $checkId) {
			if (isset($this->checks[$checkId])) {
				$checks[$checkId] = $this->checks[$checkId];
				unset($checkIds[$i]);
			}
		}

		if (empty($checkIds)) {
			return $checks;
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('flow_checks')
			->where($query->expr()->in('id', $query->createNamedParameter($checkIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$result = $query->execute();

		while ($row = $result->fetch()) {
			$this->checks[(int) $row['id']] = $row;
			$checks[(int) $row['id']] = $row;
		}
		$result->closeCursor();

		$checkIds = array_diff($checkIds, array_keys($checks));

		if (!empty($checkIds)) {
			$missingCheck = array_pop($checkIds);
			throw new \UnexpectedValueException($this->l->t('Check #%s does not exist', $missingCheck));
		}

		return $checks;
	}

	/**
	 * @param string $class
	 * @param string $operator
	 * @param string $value
	 * @return int Check unique ID
	 */
	protected function addCheck($class, $operator, $value) {
		$hash = md5($class . '::' . $operator . '::' . $value);

		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from('flow_checks')
			->where($query->expr()->eq('hash', $query->createNamedParameter($hash)));
		$result = $query->execute();

		if ($row = $result->fetch()) {
			$result->closeCursor();
			return (int) $row['id'];
		}

		$query = $this->connection->getQueryBuilder();
		$query->insert('flow_checks')
			->values([
				'class' => $query->createNamedParameter($class),
				'operator' => $query->createNamedParameter($operator),
				'value' => $query->createNamedParameter($value),
				'hash' => $query->createNamedParameter($hash),
			]);
		$query->execute();

		return $query->getLastInsertId();
	}
}
