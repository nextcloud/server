<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Model;

use OC\Async\Enum\BlockType;
use OCP\AppFramework\Db\Entity;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\Enum\BlockStatus;

/**
 * @method void setToken(string $token)
 * @method string getToken()
 * @method void setSessionToken(string $sessionToken)
 * @method string getSessionToken()
 * @method void setType(int $type)
 * @method int getType()
 * @method void setCode(string $code)
 * @method string getCode()
 * @method void setParams(array $params)
 * @method ?array getParams()
 * @method void setDataset(array $dataset)
 * @method ?array getDataset()
 * @method void setMetadata(array $metadata)
 * @method ?array getMetadata()
 * @method void setLinks(array $params)
 * @method ?array getLinks()
 * @method void setOrig(array $orig)
 * @method ?array getOrig()
 * @method void setResult(array $result)
 * @method ?array getResult()
 * @method void setStatus(int $status)
 * @method int getStatus()
 * @method void setExecutionTime(int $status)
 * @method int getExecutionTime()
 * @method void setLockToken(string $lockToken)
 * @method string getLockToken()
 * @method void setCreation(int $creation)
 * @method int getCreation()
 * @method void setLastRun(int $lastRun)
 * @method int getLastRun()
 * @method void setNextRun(int $nextRun)
 * @method int getNextRun()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Block extends Entity {
	protected string $token = '';
	protected string $sessionToken = '';
	protected int $type = 0;
	protected string $code = '';
	protected ?array $params = [];
	protected ?array $dataset = [];
	protected ?array $metadata = [];
	protected ?array $links = [];
	protected ?array $orig = [];
	protected ?array $result = [];
	protected int $status = 0;
	protected int $executionTime = 0;
	protected string $lockToken = '';
	protected int $creation = 0;
	protected int $lastRun = 0;
	protected int $nextRun = 0;

	public function __construct() {
		$this->addType('token', 'string');
		$this->addType('sessionToken', 'string');
		$this->addType('type', 'integer');
		$this->addType('code', 'string');
		$this->addType('params', 'json');
		$this->addType('dataset', 'json');
		$this->addType('metadata', 'json');
		$this->addType('links', 'json');
		$this->addType('orig', 'json');
		$this->addType('result', 'json');
		$this->addType('status', 'integer');
		$this->addType('executionTime', 'integer');
		$this->addType('lockToken', 'string');
		$this->addType('creation', 'integer');
		$this->addType('lastRun', 'integer');
		$this->addType('nextRun', 'integer');
	}

	public function setBlockType(BlockType $type): void {
		$this->setType($type->value);
	}

	public function getBlockType(): BlockType {
		return BlockType::from($this->getType());
	}

	public function setBlockStatus(BlockStatus $status): void {
		$this->setStatus($status->value);
	}

	public function getBlockStatus(): BlockStatus {
		return BlockStatus::from($this->getStatus());
	}

	public function setProcessExecutionTime(ProcessExecutionTime $time): void {
		$this->setExecutionTime($time->value);
	}

	public function getProcessExecutionTime(): ProcessExecutionTime {
		return ProcessExecutionTime::from($this->getExecutionTime());
	}

	public function addMetadata(array $metadata): self {
		$metadata = array_merge($this->metadata, $metadata);
		$this->setMetadata($metadata);
		return $this;
	}

	public function addMetadataEntry(string $key, string|int|array|bool $value): self {
		$metadata = $this->metadata;
		$metadata[$key] = $value;
		$this->setMetadata($metadata);
		return $this;
	}

	public function replay(bool $now = false): void {
		$count = $this->getMetadata()['_replay'] ?? 0;
		$next = time() + floor(6 ** ($count + 1)); // calculate delay based on count of retry
		$count = min(++$count, 5); // limit to 6^6 seconds (13h)

		if ($now) {
			$next = time();
		}

		$this->addMetadataEntry('_replay', $count);
		$this->setNextRun($next);
	}

	public function getReplayCount(): int {
		return $this->getMetadata()['_replay'] ?? 0;
	}
}
