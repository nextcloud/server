<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit;

use Psr\Log\LoggerInterface;

/**
 * Interface for a logger that logs in the audit log file instead of the normal log file
 */
interface IAuditLogger extends LoggerInterface {
}
