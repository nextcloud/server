<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\Teams\TeamAppAction;
use OCP\Teams\TeamAppActionConfirmation;
use OCP\Teams\TeamAppActionPrompt;
use OCP\Teams\TeamAppActionResult;
use OCP\Teams\TeamAppEntry;
use OCP\Teams\TeamAppNavigation;
use OCP\Teams\TeamAppResource;
use OCP\Teams\TeamAppResourceKey;
use OCP\Teams\TeamAppResourcePicker;
use OCP\Teams\TeamAppRouteAlias;
use Test\TestCase;

class TeamAppDataTest extends TestCase {
	public function testSerializesRouteAlias(): void {
		$alias = new TeamAppRouteAlias('page/:fileId', 'fileId');

		$this->assertSame('page/:fileId', $alias->path);
		$this->assertSame('fileId', $alias->entryParam);
		$this->assertSame(['path' => 'page/:fileId', 'entryParam' => 'fileId'], $alias->jsonSerialize());
	}

	public function testSerializesNestedNavigation(): void {
		$prompt = new TeamAppActionPrompt('Name', '');
		$confirmation = new TeamAppActionConfirmation('Delete', 'Delete this item?');
		$resource = new TeamAppResource('42', 'Board', '/Board.whiteboard', 'resource-icon', '<svg/>', 'emoji');
		$picker = new TeamAppResourcePicker('Add to navigation', [$resource], []);
		$action = new TeamAppAction('rename', 'Rename', 'action-icon', $prompt, $confirmation, $picker);
		$entry = new TeamAppEntry('42', 'Board', 'entry-icon', '<svg/>', 'emoji', '/board/42', ['board-42'], [$action], ['path' => '/Board.whiteboard']);
		$key = new TeamAppResourceKey('whiteboard', '42');
		$navigation = new TeamAppNavigation([$entry], [$action], [$key]);
		$expectedAction = [
			'id' => 'rename', 'label' => 'Rename', 'icon' => 'action-icon',
			'prompt' => ['title' => 'Name', 'value' => ''],
			'confirm' => ['title' => 'Delete', 'message' => 'Delete this item?'],
			'resourcePicker' => ['title' => 'Add to navigation', 'resources' => [[
				'id' => '42', 'label' => 'Board', 'description' => '/Board.whiteboard',
				'icon' => 'resource-icon', 'iconSvg' => '<svg/>', 'iconEmoji' => 'emoji',
			]], 'selectedIds' => []],
		];

		$this->assertSame([$entry], $navigation->entries);
		$this->assertSame([$action], $entry->actions);
		$this->assertSame($picker, $action->resourcePicker);
		$this->assertSame(['entries' => [[
			'id' => '42', 'label' => 'Board', 'icon' => 'entry-icon', 'iconSvg' => '<svg/>', 'iconEmoji' => 'emoji',
			'href' => '/board/42', 'legacyIds' => ['board-42'], 'actions' => [$expectedAction],
			'viewData' => ['path' => '/Board.whiteboard'],
		]], 'actions' => [$expectedAction], 'resourceKeys' => [['providerId' => 'whiteboard', 'resourceId' => '42']]], $this->decode($navigation));
	}

	public function testOmitsAbsentOptionalFields(): void {
		$this->assertSame(['entries' => []], $this->decode(new TeamAppNavigation([])));
		$this->assertSame(['id' => '42', 'label' => 'Board'], $this->decode(new TeamAppEntry('42', 'Board')));
		$this->assertSame(['id' => 'rename', 'label' => 'Rename'], $this->decode(new TeamAppAction('rename', 'Rename')));
		$this->assertSame(['title' => 'Name'], $this->decode(new TeamAppActionPrompt('Name')));
		$this->assertSame(['id' => '42', 'label' => 'Board'], $this->decode(new TeamAppResource('42', 'Board')));
		$this->assertSame(['title' => 'Boards', 'resources' => []], $this->decode(new TeamAppResourcePicker('Boards', [])));
		$this->assertSame([], $this->decode(new TeamAppActionResult()));
	}

	public function testPreservesExplicitEmptyLists(): void {
		$this->assertSame(['entries' => [], 'actions' => [], 'resourceKeys' => []], $this->decode(new TeamAppNavigation([], [], [])));
		$this->assertSame(['id' => '42', 'label' => 'Board', 'legacyIds' => [], 'actions' => [], 'viewData' => []], $this->decode(new TeamAppEntry('42', 'Board', legacyIds: [], actions: [], viewData: [])));
		$this->assertSame(['navigationEntryIds' => []], $this->decode(new TeamAppActionResult(navigationEntryIds: [])));
	}

	public function testSerializesActionResult(): void {
		$result = new TeamAppActionResult(['42'], '42');

		$this->assertSame(['42'], $result->navigationEntryIds);
		$this->assertSame('42', $result->open);
		$this->assertSame(['navigationEntryIds' => ['42'], 'open' => '42'], $this->decode($result));
		$this->assertSame(['open' => '42'], $this->decode(new TeamAppActionResult(open: '42')));
	}

	private function decode(\JsonSerializable $value): array {
		return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
	}
}
