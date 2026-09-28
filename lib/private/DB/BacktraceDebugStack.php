<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\Logging\DebugStack;

class BacktraceDebugStack extends DebugStack {
	public function startQuery($sql, ?array $params = null, ?array $types = null) {
		parent::startQuery($sql, $params, $types);
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$this->queries[$this->currentQuery]['backtrace'] = $backtrace;
		$this->queries[$this->currentQuery]['start'] = $this->start;
	}
}
