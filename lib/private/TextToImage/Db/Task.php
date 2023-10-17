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

namespace OC\TextToImage\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Image;
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
 * @method string getIdentifier()
 */
class Task extends Entity {
	protected $lastUpdated;
	protected $type;
	protected $input;
	protected $status;
	protected $userId;
	protected $appId;
	protected $identifier;

	/**
	 * @var string[]
	 */
	public static array $columns = ['id', 'last_updated', 'input', 'status', 'user_id', 'app_id', 'identifier'];

	/**
	 * @var string[]
	 */
	public static array $fields = ['id', 'lastUpdated', 'input', 'status', 'userId', 'appId', 'identifier'];


	public function __construct() {
		// add types in constructor
		$this->addType('id', 'integer');
		$this->addType('lastUpdated', 'datetime');
		$this->addType('input', 'string');
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

	public static function fromPublicTask(OCPTask $task): Task {
		/** @var Task $dbTask */
		$dbTask = Task::fromParams([
			'id' => $task->getId(),
			'lastUpdated' => time(),
			'status' => $task->getStatus(),
			'input' => $task->getInput(),
			'userId' => $task->getUserId(),
			'appId' => $task->getAppId(),
			'identifier' => $task->getIdentifier(),
		]);
		return $dbTask;
	}

	public function toPublicTask(): OCPTask {
		$task = new OCPTask($this->getInput(), $this->getAppId(), $this->getuserId(), $this->getIdentifier());
		$task->setId($this->getId());
		$task->setStatus($this->getStatus());
		$appData = \OC::$server->get(IAppDataFactory::class)->get('core');
		try {
			try {
				$folder = $appData->getFolder('text2image');
			} catch(NotFoundException) {
				$folder = $appData->newFolder('text2image');
			}
			$task->setOutputImage(new Image(base64_encode($folder->getFile((string)$task->getId())->getContent())));
		} catch (NotFoundException|NotPermittedException) {
			// noop
		}
		return $task;
	}
}
