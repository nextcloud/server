<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\DAV\Sharing;

trait SharingPrivilegeSetTrait {
	#[\Override]
	public function getSupportedPrivilegeSet(): array {
		return [
			'{DAV:}read' => [
				'abstract' => false,
				'aggregates' => [
					'{DAV:}read-acl' => ['abstract' => false, 'aggregates' => []],
					'{DAV:}read-current-user-privilege-set' => ['abstract' => false, 'aggregates' => []],
				],
			],
			'{DAV:}write' => [
				'abstract' => false,
				'aggregates' => [
					'{DAV:}write-properties' => ['abstract' => false, 'aggregates' => []],
					'{DAV:}write-content' => ['abstract' => false, 'aggregates' => []],
					'{DAV:}unlock' => ['abstract' => false, 'aggregates' => []],
					'{DAV:}bind' => ['abstract' => false, 'aggregates' => []],
					'{DAV:}unbind' => ['abstract' => false, 'aggregates' => []],
				],
			],
			'{DAV:}write-acl' => ['abstract' => false, 'aggregates' => []],
		];
	}
}
