<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCA\WorkflowEngine\AppInfo\Application;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\WorkflowEngine\IEntity;

trait TFileCheck {
	/** @var IStorage */
	protected $storage;

	/** @var string */
	protected $path;

	/** @var bool */
	protected $isDir;

	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @param bool $isDir
	 * @since 18.0.0
	 */
	public function setFileInfo(IStorage $storage, string $path, bool $isDir = false): void {
		$this->storage = $storage;
		$this->path = $path;
		$this->isDir = $isDir;
	}

	/**
	 * @throws NotFoundException
	 */
	public function setEntitySubject(IEntity $entity, $subject): void {
		if ($entity instanceof File) {
			if (!$subject instanceof Node) {
				throw new \UnexpectedValueException(
					'Expected Node subject for File entity, got {class}',
					['app' => Application::APP_ID, 'class' => get_class($subject)]
				);
			}
			$this->storage = $subject->getStorage();
			$this->path = $subject->getPath();
		}
	}
}
