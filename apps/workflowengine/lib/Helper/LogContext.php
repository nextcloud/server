<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Helper;

use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;

class LogContext {
	/** @var array */
	protected $details;

	public function setDescription(string $description): LogContext {
		$this->details['message'] = $description;
		return $this;
	}

	public function setScopes(array $scopes): LogContext {
		$this->details['scopes'] = [];
		foreach ($scopes as $scope) {
			if ($scope instanceof ScopeContext) {
				switch ($scope->getScope()) {
					case IManager::SCOPE_ADMIN:
						$this->details['scopes'][] = ['scope' => 'admin'];
						break;
					case IManager::SCOPE_USER:
						$this->details['scopes'][] = [
							'scope' => 'user',
							'uid' => $scope->getScopeId(),
						];
						break;
					default:
						continue 2;
				}
			}
		}
		return $this;
	}

	public function setOperation(?IOperation $operation): LogContext {
		if ($operation instanceof IOperation) {
			$this->details['operation'] = [
				'class' => get_class($operation),
				'name' => $operation->getDisplayName(),
			];
		}
		return $this;
	}

	public function setEntity(?IEntity $entity): LogContext {
		if ($entity instanceof IEntity) {
			$this->details['entity'] = [
				'class' => get_class($entity),
				'name' => $entity->getName(),
			];
		}
		return $this;
	}

	public function setConfiguration(array $configuration): LogContext {
		$this->details['configuration'] = $configuration;
		return $this;
	}

	public function setEventName(string $eventName): LogContext {
		$this->details['eventName'] = $eventName;
		return $this;
	}

	public function getDetails(): array {
		return $this->details;
	}
}
