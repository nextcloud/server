<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Sharing;

use OCA\DAV\DAV\Sharing\SharingMapper;
use OCA\DAV\DAV\Sharing\SharingService;

class Service extends SharingService {
	protected string $resourceType = 'calendar';
	public function __construct(
		protected SharingMapper $mapper,
	) {
		parent::__construct($mapper);
	}
}
