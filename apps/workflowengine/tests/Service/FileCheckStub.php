<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Service;

use OCP\Files\Storage\IStorage;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IFileCheck;

/**
 * Concrete stub that satisfies both IFileCheck and ICheck, since IFileCheck does not extend ICheck
 * but all real implementations are expected to implement both.
 */
class FileCheckStub implements IFileCheck, ICheck {
	public function setFileInfo(IStorage $storage, string $path, bool $isDir = false): void {
	}

	public function setEntitySubject(IEntity $entity, $subject): void {
	}

	public function executeCheck($operator, $value): bool {
		return true;
	}

	public function validateCheck($operator, $value): void {
	}

	public function supportedEntities(): array {
		return [];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
