<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OC\Files\Storage\Local;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\Mount\IMountManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\WorkflowEngine\IFileCheck;

class FileName extends AbstractStringCheck implements IFileCheck {
	use TFileCheck;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 */
	public function __construct(
		IL10N $l,
		protected IRequest $request,
		private IMountManager $mountManager,
	) {
		parent::__construct($l);
	}

	/**
	 * @return string
	 */
	protected function getActualValue(): string {
		$fileName = $this->path === null ? '' : basename($this->path);
		if ($fileName === '' && (!$this->storage->isLocal() || $this->storage->instanceOfStorage(Local::class))) {
			// Return the mountpoint name of external storage that are not mounted as user home
			$mountPoints = $this->mountManager->findByStorageId($this->storage->getId());
			if (empty($mountPoints) || $mountPoints[0]->getMountType() !== 'external') {
				return $fileName;
			}
			$mountPointPath = rtrim($mountPoints[0]->getMountPoint(), '/');
			$mountPointPieces = explode('/', $mountPointPath);
			$mountPointName = array_pop($mountPointPieces);
			if (!empty($mountPointName) && $mountPointName !== 'files' && count($mountPointPieces) !== 2) {
				return $mountPointName;
			}
		}
		return $fileName;
	}

	/**
	 * @param string $operator
	 * @param string $checkValue
	 * @param string $actualValue
	 * @return bool
	 */
	protected function executeStringCheck($operator, $checkValue, $actualValue): bool {
		if ($operator === 'is' || $operator === '!is') {
			$checkValue = mb_strtolower($checkValue);
			$actualValue = mb_strtolower($actualValue);
		}
		return parent::executeStringCheck($operator, $checkValue, $actualValue);
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
