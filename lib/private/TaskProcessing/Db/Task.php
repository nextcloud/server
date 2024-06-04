<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TaskProcessing\Db;

use OCP\AppFramework\Db\Entity;
use OCP\TaskProcessing\Task as OCPTask;

/**
 * @method setType(string $type)
 * @method string getType()
 * @method setLastUpdated(int $lastUpdated)
 * @method int getLastUpdated()
 * @method setStatus(int $status)
 * @method int getStatus()
 * @method setOutput(string $output)
 * @method string getOutput()
 * @method setInput(string $input)
 * @method string getInput()
 * @method setUserId(?string $userId)
 * @method string|null getUserId()
 * @method setAppId(string $type)
 * @method string getAppId()
 * @method setCustomId(string $customId)
 * @method string getCustomId()
 * @method setCompletionExpectedAt(null|\DateTime $completionExpectedAt)
 * @method null|\DateTime getCompletionExpectedAt()
 * @method setErrorMessage(null|string $error)
 * @method null|string getErrorMessage()
 * @method setProgress(null|float $progress)
 * @method null|float getProgress()
 */
class Task extends Entity {
	protected $lastUpdated;
	protected $type;
	protected $input;
	protected $output;
	protected $status;
	protected $userId;
	protected $appId;
	protected $customId;
	protected $completionExpectedAt;
	protected $errorMessage;
	protected $progress;

	/**
	 * @var string[]
	 */
	public static array $columns = ['id', 'last_updated', 'type', 'input', 'output', 'status', 'user_id', 'app_id', 'custom_id', 'completion_expected_at', 'error_message', 'progress'];

	/**
	 * @var string[]
	 */
	public static array $fields = ['id', 'lastUpdated', 'type', 'input', 'output', 'status', 'userId', 'appId', 'customId', 'completionExpectedAt', 'errorMessage', 'progress'];


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
		$this->addType('customId', 'string');
		$this->addType('completionExpectedAt', 'datetime');
		$this->addType('errorMessage', 'string');
		$this->addType('progress', 'float');
	}

	public function toRow(): array {
		return array_combine(self::$columns, array_map(function ($field) {
			return $this->{'get'.ucfirst($field)}();
		}, self::$fields));
	}

	public static function fromPublicTask(OCPTask $task): self {
		/** @var Task $taskEntity */
		$taskEntity = self::fromParams([
			'id' => $task->getId(),
			'type' => $task->getTaskTypeId(),
			'lastUpdated' => time(),
			'status' => $task->getStatus(),
			'input' => json_encode($task->getInput(), JSON_THROW_ON_ERROR),
			'output' => json_encode($task->getOutput(), JSON_THROW_ON_ERROR),
			'errorMessage' => $task->getErrorMessage(),
			'userId' => $task->getUserId(),
			'appId' => $task->getAppId(),
			'customId' => $task->getCustomId(),
			'completionExpectedAt' => $task->getCompletionExpectedAt(),
			'progress' => $task->getProgress(),
		]);
		return $taskEntity;
	}

	/**
	 * @return OCPTask
	 * @throws \JsonException
	 */
	public function toPublicTask(): OCPTask {
		$task = new OCPTask($this->getType(), json_decode($this->getInput(), true, 512, JSON_THROW_ON_ERROR), $this->getAppId(), $this->getuserId(), $this->getCustomId());
		$task->setId($this->getId());
		$task->setStatus($this->getStatus());
		$task->setLastUpdated($this->getLastUpdated());
		$task->setOutput(json_decode($this->getOutput(), true, 512, JSON_THROW_ON_ERROR));
		$task->setCompletionExpectedAt($this->getCompletionExpectedAt());
		$task->setErrorMessage($this->getErrorMessage());
		$task->setProgress($this->getProgress());
		return $task;
	}
}
