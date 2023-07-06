<?php

namespace OC\LanguageModel\Db;

use OCP\AppFramework\Db\Entity;
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
		return Task::fromParams([
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
	}
}
