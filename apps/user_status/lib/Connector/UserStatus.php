<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
 *
 */
namespace OCA\UserStatus\Connector;

use DateTimeImmutable;
use OCA\UserStatus\Db;
use OCP\UserStatus\IUserStatus;

class UserStatus implements IUserStatus {

	/** @var string */
	private $userId;

	/** @var string */
	private $status;

	/** @var string|null */
	private $message;

	/** @var string|null */
	private $icon;

	/** @var DateTimeImmutable|null */
	private $clearAt;

	/** @var Db\UserStatus */
	private $internalStatus;

	public function __construct(Db\UserStatus $status) {
		$this->internalStatus = $status;
		$this->userId = $status->getUserId();
		$this->status = $status->getStatus();
		$this->message = $status->getCustomMessage();
		$this->icon = $status->getCustomIcon();

		if ($status->getStatus() === IUserStatus::INVISIBLE) {
			$this->status = IUserStatus::OFFLINE;
		}
		if ($status->getClearAt() !== null) {
			$this->clearAt = DateTimeImmutable::createFromFormat('U', (string)$status->getClearAt());
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @inheritDoc
	 */
	public function getStatus(): string {
		return $this->status;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): ?string {
		return $this->message;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): ?string {
		return $this->icon;
	}

	/**
	 * @inheritDoc
	 */
	public function getClearAt(): ?DateTimeImmutable {
		return $this->clearAt;
	}

	public function getInternal(): Db\UserStatus {
		return $this->internalStatus;
	}
}
