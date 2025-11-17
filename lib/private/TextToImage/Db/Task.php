<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\TextToImage\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Server;
use OCP\TextToImage\Task as OCPTask;

/**
 * @method setLastUpdated(DateTime $lastUpdated)
 * @method DateTime getLastUpdated()
 * @method setInput(string $type)
 * @method string getInput()
 * @method setResultPath(string $resultPath)
 * @method string getResultPath()
 * @method setStatus(int $type)
 * @method int getStatus()
 * @method setUserId(?string $userId)
 * @method string|null getUserId()
 * @method setAppId(string $type)
 * @method string getAppId()
 * @method setIdentifier(string $identifier)
 * @method string|null getIdentifier()
 * @method setNumberOfImages(int $numberOfImages)
 * @method int getNumberOfImages()
 * @method setCompletionExpectedAt(DateTime $at)
 * @method DateTime getCompletionExpectedAt()
 */
class Task extends Entity {
	protected $lastUpdated;
	protected $type;
	protected $input;
	protected $status;
	protected $userId;
	protected $appId;
	protected $identifier;
	protected $numberOfImages;
	protected $completionExpectedAt;

	/**
	 * @var string[]
	 */
	public static array $columns = ['id', 'last_updated', 'input', 'status', 'user_id', 'app_id', 'identifier', 'number_of_images', 'completion_expected_at'];

	/**
	 * @var string[]
	 */
	public static array $fields = ['id', 'lastUpdated', 'input', 'status', 'userId', 'appId', 'identifier', 'numberOfImages', 'completionExpectedAt'];


	public function __construct() {
		// add types in constructor
		$this->addType('id', 'integer');
		$this->addType('lastUpdated', 'datetime');
		$this->addType('input', 'string');
		$this->addType('status', 'integer');
		$this->addType('userId', 'string');
		$this->addType('appId', 'string');
		$this->addType('identifier', 'string');
		$this->addType('numberOfImages', 'integer');
		$this->addType('completionExpectedAt', 'datetime');
	}

	public function toRow(): array {
		return array_combine(self::$columns, array_map(function ($field) {
			return $this->{'get' . ucfirst($field)}();
		}, self::$fields));
	}

	public static function fromPublicTask(OCPTask $task): Task {
		/** @var Task $dbTask */
		$dbTask = Task::fromParams([
			'id' => $task->getId(),
			'lastUpdated' => Server::get(ITimeFactory::class)->getDateTime(),
			'status' => $task->getStatus(),
			'numberOfImages' => $task->getNumberOfImages(),
			'input' => $task->getInput(),
			'userId' => $task->getUserId(),
			'appId' => $task->getAppId(),
			'identifier' => $task->getIdentifier(),
			'completionExpectedAt' => $task->getCompletionExpectedAt(),
		]);
		return $dbTask;
	}

	public function toPublicTask(): OCPTask {
		$task = new OCPTask($this->getInput(), $this->getAppId(), $this->getNumberOfImages(), $this->getuserId(), $this->getIdentifier());
		$task->setId($this->getId());
		$task->setStatus($this->getStatus());
		$task->setCompletionExpectedAt($this->getCompletionExpectedAt());
		return $task;
	}
}
