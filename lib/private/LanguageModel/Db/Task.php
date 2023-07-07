<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\LanguageModel\Db;

use OCP\AppFramework\Db\Entity;
use OCP\LanguageModel\AbstractLanguageModelTask;
use OCP\LanguageModel\ILanguageModelTask;

/**
 * @method setType(string $type)
 * @method string getType()
 * @method setLastUpdated(int $lastUpdated)
 * @method int getLastUpdated()
 * @method setInput(string $type)
 * @method string getInput()
 * @method setOutput(string $type)
 * @method string getOutput()
 * @method setStatus(int $type)
 * @method int getStatus()
 * @method setUserId(string $type)
 * @method string getuserId()
 * @method setAppId(string $type)
 * @method string getAppId()
 * @method setIdentifier(string $type)
 * @method string getIdentifier()
 */
class Task extends Entity {
	protected $lastUpdated;
	protected $type;
	protected $input;
	protected $output;
	protected $status;
	protected $userId;
	protected $appId;
	protected $identifier;

	/**
	 * @var string[]
	 */
	public static array $columns = ['id', 'last_updated', 'type', 'input', 'output', 'status', 'user_id', 'app_id', 'identifier'];

	/**
	 * @var string[]
	 */
	public static array $fields = ['id', 'lastUpdated', 'type', 'input', 'output', 'status', 'userId', 'appId', 'identifier'];


	public function __construct() {
		// add types in constructor
		$this->addType('id', 'integer');
		$this->addType('lastUpdated', 'integer');
		$this->addType('type', 'string');
		$this->addType('input', 'string');
		$this->addType('output', 'string');
		$this->addType('status', 'integer');
		$this->addType('userId', 'string');
		$this->addType('appId', 'string');
		$this->addType('identifier', 'string');
	}

	public function toRow(): array {
		return array_combine(self::$columns, array_map(function ($field) {
			return $this->{'get'.ucfirst($field)}();
		}, self::$fields));
	}

	public static function fromLanguageModelTask(ILanguageModelTask $task): Task {
		/** @var Task $task */
		$task = Task::fromParams([
			'id' => $task->getId(),
			'type' => $task->getType(),
			'lastUpdated' => time(),
			'status' => $task->getStatus(),
			'input' => $task->getInput(),
			'output' => $task->getOutput(),
			'userId' => $task->getUserId(),
			'appId' => $task->getAppId(),
			'identifier' => $task->getIdentifier(),
		]);
		return $task;
	}

	public function toLanguageModelTask(): ILanguageModelTask {
		$task = AbstractLanguageModelTask::factory($this->getType(), $this->getInput(), $this->getuserId(), $this->getAppId(), $this->getIdentifier());
		$task->setId($this->getId());
		$task->setStatus($this->getStatus());
		$task->setOutput($this->getOutput());
		return $task;
	}
}
