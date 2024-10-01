<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Controller;

use OCA\WorkflowEngine\Helper\ScopeContext;
use OCP\WorkflowEngine\IManager;

class GlobalWorkflowsController extends AWorkflowController {

	/** @var ScopeContext */
	private $scopeContext;

	protected function getScopeContext(): ScopeContext {
		if ($this->scopeContext === null) {
			$this->scopeContext = new ScopeContext(IManager::SCOPE_ADMIN);
		}
		return $this->scopeContext;
	}
}
