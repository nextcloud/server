<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

class QueryPlanRecorderTest extends TestCase {
	private const INDEXES = [
		'preferences' => ['userid', 'appid', 'configkey', 'indexed', 'flags', 'lazy'],
		'filecache' => ['fileid', 'storage', 'path_hash'],
	];

	public static function providePlans(): array {
		return [
			'function on an indexed column' => [[
				'Node Type' => 'Bitmap Heap Scan', 'Relation Name' => 'oc_preferences',
				'Recheck Cond' => "(((appid)::text = 'settings'::text) AND ((configkey)::text = 'email'::text))",
				'Filter' => "(lower((indexed)::text) = 'a@example.com'::text)",
			], ['preferences']],
			'plain comparison the planner left to a filter' => [[
				'Node Type' => 'Bitmap Heap Scan', 'Relation Name' => 'oc_preferences',
				'Recheck Cond' => "(((appid)::text = 'core'::text) AND ((configkey)::text = 'lang'::text))",
				'Filter' => "((indexed)::text = 'de'::text)",
			], []],
			'function on a column no index covers' => [[
				'Node Type' => 'Index Scan', 'Relation Name' => 'oc_preferences',
				'Index Cond' => "(((appid)::text = 'core'::text) AND ((configkey)::text = 'lang'::text))",
				'Filter' => "(lower((configvalue)::text) = 'de'::text)",
			], []],
			'column name inside a literal' => [[
				'Node Type' => 'Index Scan', 'Relation Name' => 'oc_preferences',
				'Index Cond' => "(((appid)::text = 'core'::text) AND ((configkey)::text = 'lang'::text))",
				'Filter' => "(lower((configvalue)::text) = 'lower(indexed)'::text)",
			], []],
			'filtered seq scan, nested' => [[
				'Node Type' => 'Nested Loop',
				'Plans' => [
					['Node Type' => 'Seq Scan', 'Relation Name' => 'oc_filecache', 'Filter' => '(size > 0)'],
					['Node Type' => 'Seq Scan', 'Relation Name' => 'oc_mimetypes', 'Filter' => "(mimetype = 'x')"],
				],
			], ['filecache']],
			'unfiltered seq scan' => [['Node Type' => 'Seq Scan', 'Relation Name' => 'oc_filecache'], []],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('providePlans')]
	public function testUnindexedFilters(array $plan, array $expected): void {
		$this->assertSame($expected, QueryPlanRecorder::unindexedFilters($plan, 'oc_', self::INDEXES));
	}
}
