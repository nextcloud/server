<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Storage\IStorage;

/**
 * @since 18.0.0
 */
class FileCacheUpdated extends Event {
	/** @var IStorage */
	private $storage;

	/** @var string */
	private $path;

	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @since 18.0.0
	 */
	public function __construct(IStorage $storage,
		string $path) {
		parent::__construct();
		$this->storage = $storage;
		$this->path = $path;
	}

	/**
	 * @return IStorage
	 * @since 18.0.0
	 */
	public function getStorage(): IStorage {
		return $this->storage;
	}

	/**
	 * @return string
	 * @since 18.0.0
	 */
	public function getPath(): string {
		return $this->path;
	}
}
