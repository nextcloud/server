<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV\Import;

enum ImportDisposition: string {
	case Created = 'created';
	case Updated = 'updated';
	case Exists = 'exists';
	case Error = 'error';
}
