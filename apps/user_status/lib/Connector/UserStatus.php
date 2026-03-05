<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function __construct(
		private Db\UserStatus $internalStatus,
	) {
		$this->userId = $this->internalStatus->getUserId();
		$this->status = $this->internalStatus->getStatus();
		$this->message = $this->internalStatus->getCustomMessage();
		$this->icon = $this->internalStatus->getCustomIcon();

		if ($this->internalStatus->getStatus() === IUserStatus::INVISIBLE) {
			$this->status = IUserStatus::OFFLINE;
		}
		if ($this->internalStatus->getClearAt() !== null) {
			$this->clearAt = DateTimeImmutable::createFromFormat('U', (string)$this->internalStatus->getClearAt());
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
