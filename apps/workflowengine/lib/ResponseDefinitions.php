<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine;

use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IEntityEvent;
use OCP\WorkflowEngine\IOperation;

/**
 * @psalm-type WorkflowEngineOperator = 'is'|'in'|'match'|'less'|'greater'|'matchesIPv4'|'matchesIPv6'|"!is"|"!in"|"!match"|"!less"|"!greater"|"!matchesIPv4"|"!matchesIPv6"
 *
 * @psalm-type WorkflowEngineCheck = array{
 *   class: class-string<ICheck>,
 *   value: string,
 *   operator: WorkflowEngineOperator,
 * }
 *
 * @psalm-type WorkflowEngineRule = array{
 *   id: int,
 *   class: class-string<IOperation>,
 *   name: string,
 *   checks: list<WorkflowEngineCheck>,
 *   operation: string,
 *   entity: class-string<IEntity>,
 *   events: list<class-string<IEntityEvent>>,
 * }
 */
class ResponseDefinitions {
}
