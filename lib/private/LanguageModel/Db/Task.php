<?php

namespace OC\LanguageModel\Db;

use OCP\AppFramework\Db\Entity;
use OCP\LanguageModel\ILanguageModelTask;

/**
 * @method setType(string $type)
 * @method string getType()
 * @method setInput(string $type)
 * @method string getInput()
 * @method setStatus(int $type)
 * @method int getStatus()
 * @method setUserId(string $type)
 * @method string getuserId()
 * @method setAppId(string $type)
 * @method string getAppId()
 */
class Task extends Entity {
	protected $type;
	protected $input;
	protected $status;
	protected $userId;
	protected $appId;

	/**
	 * @var string[]
	 */
	public static array $columns = ['id', 'type', 'input', 'output', 'status', 'user_id', 'app_id'];

	/**
	 * @var string[]
	 */
	public static array $fields = ['id', 'type', 'input', 'output', 'status', 'userId', 'appId'];


	public function __construct() {
		// add types in constructor
		$this->addType('id', 'integer');
		$this->addType('type', 'string');
		$this->addType('input', 'string');
		$this->addType('status', 'integer');
		$this->addType('userId', 'string');
		$this->addType('appId', 'string');
	}

	public static function fromLanguageModelTask(ILanguageModelTask $task): Task {
		return Task::fromParams([
			'type' => $task->getType(),
			'status' => $task->getStatus(),
			'input' => $task->getInput(),
			'output' => $task->getOutput(),
			'userId' => $task->getUserId(),
			'appId' => $task->getAppId(),
		]);
	}
}
