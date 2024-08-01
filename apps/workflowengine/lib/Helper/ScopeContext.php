<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Helper;

use OCP\WorkflowEngine\IManager;

class ScopeContext {
	/** @var int */
	private $scope;
	/** @var string */
	private $scopeId;
	/** @var string */
	private $hash;

	public function __construct(int $scope, ?string $scopeId = null) {
		$this->scope = $this->evaluateScope($scope);
		$this->scopeId = $this->evaluateScopeId($scopeId);
	}

	private function evaluateScope(int $scope): int {
		if (in_array($scope, [IManager::SCOPE_ADMIN, IManager::SCOPE_USER], true)) {
			return $scope;
		}
		throw new \InvalidArgumentException('Invalid scope');
	}

	private function evaluateScopeId(?string $scopeId = null): string {
		if ($this->scope === IManager::SCOPE_USER
			&& trim((string)$scopeId) === '') {
			throw new \InvalidArgumentException('user scope requires a user id');
		}
		return trim((string)$scopeId);
	}

	/**
	 * @return int
	 */
	public function getScope(): int {
		return $this->scope;
	}

	/**
	 * @return string
	 */
	public function getScopeId(): string {
		return $this->scopeId;
	}

	public function getHash(): string {
		if ($this->hash === null) {
			$this->hash = \hash('sha256', $this->getScope() . '::' . $this->getScopeId());
		}
		return $this->hash;
	}
}
